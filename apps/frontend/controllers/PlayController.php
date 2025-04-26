<?php

namespace Play\Frontend\Controllers;

use Phalcon\Db,
  \QuestionTypes,
  \BonusQuestions,
  \CustomAnswers,
  \Answers,
  \Routes,
  \Exception;

class PlayController extends ControllerBase
{

  public function indexAction()
  {
    if ($this->requirePlayer())
      return true;

    $thisTeam = $this->team;

    if (is_null($thisTeam->activation))
      return $this->response->redirect('activate');

    $thisOrderHunt = $this->orderHunt;

    $redis = $this->redis;
    $request = $this->request;

    $hunt = $this->hunt;

    // Set current status
    $teamsStatus = $thisOrderHunt->getTeamsStatus(true, true);
    foreach ($teamsStatus as $team) {
      if ($team['id'] == $thisTeam->id) {
        $teamStatus = $this->view->teamStatus = $team;
        break;
      }
    }
//var_dump($thisOrderHunt->Teams->toArray());die;
    $questions = $this->db->fetchAll(<<<EOF
SELECT q.id, q.point_id, q.type_id, q.name, q.question, q.qattachment, q.hint,  
  q.funfact, q.response_correct, q.answers, q.attachment, q.timeout, 0 AS `customq`, 
  IF(q.score IS NULL,qt.score,q.score) as `cscore`, rp.idx, a.created, 
  qt.type as `question_type`, qt.limitAnswers, a.action as `answer_action`, a.funfact_viewed, a.id as aid, q.disable_hint, q.disable_skip 
FROM route_points rp 
  LEFT JOIN hunt_points hp ON (rp.hunt_point_id = hp.id) 
  LEFT JOIN questions q ON (hp.question_id = q.id) 
  LEFT JOIN question_types qt ON (q.type_id = qt.id) 
  LEFT JOIN answers a ON (a.team_id = {$thisTeam->id} AND a.hunt_id = {$thisOrderHunt->hunt_id} AND a.question_id = q.id) 
WHERE rp.route_id = {$thisTeam->route_id} 
UNION ALL SELECT cq.id, NULL as `point_id`, cq.type_id, cq.name, cq.question, cq.qattachment, cq.hint, cq.funfact, 
    cq.response_correct, cq.answers, cq.attachment, cq.timeout, 1 AS `customq`, cq.score as `cscore`, cq.idx, ca.created, 
    qt.type as `question_type`, qt.limitAnswers, ca.action as `answer_action`, ca.funfact_viewed, ca.id as aid, NULL as `disable_hint`, NULL as `isable_skip` 
FROM custom_questions cq 
  LEFT JOIN question_types qt ON (cq.type_id = qt.id) 
  LEFT JOIN custom_answers ca ON (ca.team_id = {$thisTeam->id} AND ca.custom_question_id = cq.id) 
WHERE cq.order_hunt_id = {$thisOrderHunt->id} 
ORDER BY idx ASC, type_id DESC
EOF
    , Db::FETCH_ASSOC);

    if (defined('noScore')) {
      foreach ($questions as $i => $q)
        $questions[$i]['cscore'] = 0;
    }

    $strategy = $this->view->strategy = $hunt->isStrategyHunt();
    if ($strategy) {
      $_questions = $questions;
      $currentPosX = 0;
      $doSort = false;
      $answeredAll = true;
      foreach ($questions as $i => $q) {
        if ($strategy && $q['question_type'] != QuestionTypes::Photo)
          $_questions[$i]['limitAnswers'] = $questions[$i]['limitAnswers'] = $q['limitAnswers'] = '1';
        if (is_null($q['answer_action'])) {
          $answeredAll = false;
        } else {
          $currentPosX++;
          if (is_null($q['funfact_viewed']))
            $doSort = true;
        }
      }
      if ($answeredAll && !$doSort) {
        $this->view->questions = $questions;
        $strategy = false; // to skip to the end msg
      }
      if ($doSort) {
        usort($questions, function($q1, $q2){
          $a = !is_null($q1['answer_action']) && is_null($q1['funfact_viewed']) && !(empty($q1['funfact']) || $q1['question_type'] == QuestionTypes::Other) ? 0 : 1;
          $b = !is_null($q2['answer_action']) && is_null($q2['funfact_viewed']) && !(empty($q2['funfact']) || $q2['question_type'] == QuestionTypes::Other) ? 0 : 1;
          return $a === $b ? $q1['idx'] - $q2['idx'] : ($a - $b);
        });
      }
    }

    $question = null;
    $ffViewed = false;
    foreach ($questions as $i => $q) {
      if (is_null($q['answer_action']) || $ffViewed) {
        $q['currentPos'] = $i + 1;
        $q['numQuestions'] = count($questions);
        $question = $q;
        break;
      }
      $ffViewed = is_null($q['funfact_viewed']) && $q['question_type'] != QuestionTypes::Other && ($q['answer_action'] == Answers::Skipped || !empty($q['funfact']));
    }
    $this->view->isLeader = $isLeader = $this->player->isLeader();

    if ($strategy) {
      if ((int)$request->getQuery('back') === 1) {
        $redis->delete(SB_PREFIX . 'strategy:' . $thisOrderHunt->id . ':' . $thisTeam->id);
        return $this->response->redirect('play');
      }
      if ($ffViewed) {
        if ($isLeader)
          $redis->delete(SB_PREFIX . 'strategy:' . $thisOrderHunt->id . ':' . $thisTeam->id);
      } else {
        if ($isLeader && ($qid = (int)$request->getQuery('q', 'int')) > 0) {
          foreach ($_questions as $q) {
            if ($qid === (int)$q['id']) {
              if (!is_null($q['answer_action'])) {
                /*if ($q['answer_action'] == Answers::Skipped) {
                  if (!$this->db->delete('answers', 'team_id=' . (int)$thisTeam->id . ' AND hunt_id=' . (int)$hunt->id . ' AND question_id=' . (int)$q['id'])) {
                    try {
                      $this->logger->critical("Failed to delete skipped answer: (player {$this->player->id}) " . $q['id']);
                    } catch(Exception $e) { }
                  }
                } else {*/
                  break;
                //}
              }
              if ($q['timeout'] > 0) {
                $redis->set(SB_PREFIX . 'strategyp:' . $thisOrderHunt->id . ':' . $thisTeam->id, time() + 1, 86400);
                $redis->delete(SB_PREFIX . 'qtimeout:' . $thisTeam->id . ':' . $q['id']);
              }
              $redis->delete(SB_PREFIX . 'bqchooseskip:' . $thisTeam->id . ':' . $q['id']);
              $redis->delete(SB_PREFIX . 'answerslimit:' . $thisTeam->id . ':' . $q['id']);
              $redis->set(SB_PREFIX . 'strategy:' . $thisOrderHunt->id . ':' . $thisTeam->id, $qid, 86400);
              break;
            }
          }
          return $this->response->redirect('play');
        }
        $qid = (int)$redis->get(SB_PREFIX . 'strategy:' . $thisOrderHunt->id . ':' . $thisTeam->id);
        if ($qid > 0) {
          $found = false;
          foreach ($questions as $q) {
            if ($qid === (int)$q['id']) {
              $question = array_merge($question, $q);
              $found = true;
              break;
            }
          }
          if (!$found || !is_null($q['answer_action'])) {
            $redis->delete(SB_PREFIX . 'strategy:' . $thisOrderHunt->id . ':' . $thisTeam->id);
            return $this->response->redirect('play');
          }
        } else {
          if ($redis->get(SB_PREFIX . 'ohloc:' . $thisOrderHunt->id . ':' . $thisTeam->id) != 'strategy') {
            if (($fbr = str_replace('"', '', $this->firebase->set(FB_PREFIX . 'orderhuntloc/' . $thisTeam->id, 'strategy', [], 3))) != 'strategy') {
              try {
                $this->logger->critical("Firebase error10: (player {$this->player->id}) " . $fbr);
              } catch(Exception $e) { }
            } else {
              $redis->set(SB_PREFIX . 'ohloc:' . $thisOrderHunt->id . ':' . $thisTeam->id, 'strategy', 60);
            }
          }

          if (isset($currentPosX)) {
            $question['currentPos'] = $currentPosX;
            $question['numQuestions'] = count($questions);
          }

          $this->view->question = $question;
          $this->view->questions = $questions;
          $this->view->firebase = [
            'config' => $this->config->firebase,
            'appLoc' => [
              (int)$thisTeam->id,
              'orderHunt' => (int)$thisOrderHunt->id,
              'orderId' => (int)$thisOrderHunt->order_id,
              'timeLeft' => max(-1, strtotime($thisOrderHunt->finish) - time())
            ]
          ];
          define('cacheFileId', '137');
          $this->assets->collection('script')->addJs('/js/app/strategy.js');
          $this->view->orderHuntId = $thisOrderHunt->id;
          return $this->view->pick('play/strategy');
        }
      }
    }

    $break = $this->hunt->checkBreakpoints($thisOrderHunt, true);
    if ($break !== false) {
      $doBreak = true;
      if (count($break) === 1) {
        $this->view->firebase = [
          'config' => $this->config->firebase,
          'appLoc' => [
            (int)$thisTeam->id,
            'orderHunt' => (int)$thisOrderHunt->id,
            'orderId' => (int)$thisOrderHunt->order_id,
            'timeLeft' => max(-1, strtotime($thisOrderHunt->finish) - time())
          ]
        ];
        $this->view->lateTeams = [];
        //var_dump(SB_PREFIX . 'breakp:' . $thisOrderHunt->id . ':' . $break[0]);
        if ($breakTime = $redis->get(SB_PREFIX . 'breakp:' . $thisOrderHunt->id . ':' . $break[0])) {
          $this->view->timeCounter = min(30, max(3, $breakTime - time()));
        }
        else
          $this->view->timeCounter = 30;
      } else if (!in_array($thisTeam->id, $break[1])) {
        $this->view->timeCounter = 30;
        $this->view->lateTeams = $break[1];
        $this->view->firebase = [
          'config' => $this->config->firebase,
          'appLoc' => [
            (int)$thisTeam->id,
            'orderHunt' => (int)$thisOrderHunt->id,
            'orderId' => (int)$thisOrderHunt->order_id,
            'timeLeft' => max(-1, strtotime($thisOrderHunt->finish) - time())
          ]
        ];
      } else {
        $doBreak = false;
      }
      if ($doBreak) {
        if ($redis->get(SB_PREFIX . 'ohloc:' . $thisOrderHunt->id . ':' . $thisTeam->id) != 'break') {
          if (($fbr = str_replace('"', '', $this->firebase->set(FB_PREFIX . 'orderhuntloc/' . $thisTeam->id, 'break', [], 3))) != 'break') {
            try {
              $this->logger->critical("Firebase error9: (player {$this->player->id}) " . $fbr);
            } catch(Exception $e) { }
          } else {
            $redis->set(SB_PREFIX . 'ohloc:' . $thisOrderHunt->id . ':' . $thisTeam->id, 'break', 60);
          }
        }
        if (!is_null($question)) {
          if (isset($currentPosX))
            $question['currentPos'] = $currentPosX;
          $this->view->question = $question;
        }
        $tids = implode(',', array_map(function($t){
          return $t['id'];
        }, $teamsStatus));
        $lastAnswers = $this->db->fetchAll(<<<EOF
SELECT ta.team_id as `id`, MAX(ta.created) as `created` FROM (
  SELECT a.team_id, a.created FROM answers a
    WHERE a.team_id IN ({$tids}) AND a.hunt_id = {$this->orderHunt->hunt_id}
  UNION ALL SELECT ca.team_id, ca.created FROM custom_answers ca 
    WHERE ca.order_hunt_id = {$this->orderHunt->id} 
) AS ta 
GROUP BY ta.team_id 
ORDER BY ta.created DESC
EOF
        , Db::FETCH_ASSOC);
        foreach ($lastAnswers as $a) {
          foreach ($teamsStatus as $i => $t) {
            if ($t['id'] == $a['id']) {
              $teamsStatus[$i]['lastAnswer'] = $a['created'];
              break;
            }
          }
        }
        usort($teamsStatus, function($a, $b){
          $c = $b['count'] <=> $a['count'];
          return $c === 0 ? (($a['lastAnswer'] ?? 0) <=> ($b['lastAnswer'] ?? 0)) : $c;
        });
        $this->view->teamsStatus = $teamsStatus;
        $this->view->breakPoint = $break[0];
        define('cacheFileId', '136');
        $this->assets->collection('script')->addJs('/js/app/break.js');
        return $this->view->pick('play/break');
      }
    }

    $isPost = $request->isPost();
    $time = time();
    $startTime = strtotime($thisOrderHunt->start);

    $lastSkip = false;
    $lastAnswer = isset($teamStatus) ? max($startTime, strtotime($teamStatus['activation'])) : $time;
    $cp = is_null($question) ? count($questions) + 1 : $question['currentPos'];
    if ($cp > 1) {
      if (is_array($responseMsg = $questions[$cp - 2])) {
        $this->view->answerAction = $responseMsg['answer_action'];
        $ffViewed = !is_null($responseMsg['funfact_viewed']);
        $lastAnswer = strtotime($ffViewed ? $responseMsg['funfact_viewed'] : $responseMsg['created']);
        if ($ffViewed || $responseMsg['question_type'] == QuestionTypes::Other) {
          $responseMsg = false;
        } if ($responseMsg['answer_action'] == Answers::Skipped) {
          if (!is_null($question))
            $question['currentPos']--;
          $lastSkip = true;
          if ($responseMsg['timeout'] > 0 && $redis->exists(SB_PREFIX . 'qtimeout:' . $thisTeam->id . ':' . $responseMsg['id']))
            $lastSkip = 3;
          else if ($responseMsg['limitAnswers'] === '1' && max(0, $redis->get(SB_PREFIX . 'answerslimit:' . $thisTeam->id . ':' . $responseMsg['id'])) >= 3)
            $lastSkip = 2;
          if ($responseMsg['question_type'] == QuestionTypes::Completion) {
            $answer = json_decode($responseMsg['answers'], true);
            $answer = is_array($answer) && isset($answer['w']) ? $answer['w'] : '';
          } else if ($responseMsg['question_type'] == QuestionTypes::Photo || $responseMsg['question_type'] == QuestionTypes::Other) {
            $answer = '';
          } else if ($responseMsg['question_type'] == QuestionTypes::Choose) {
            if ($redis->exists(SB_PREFIX . 'bqchooseskip:' . $thisTeam->id . ':' . $responseMsg['id']))
              $lastSkip = 2;
            $answers = explode("\n", $responseMsg['answers']);
            $answer = '';
            foreach ($answers as $a) {
              if (substr($a, 0, 1) == '*') {
                $answer = ltrim($a, ' *');
                break;
              }
            }
          } else {
            $answer = empty($responseMsg['answers']) && $responseMsg['answers'] !== '0' ? '' : explode("\n", $responseMsg['answers']);
            if (!empty($answer)) {
              $answer = trim(array_shift($answer), ' *');
              if (mb_strtolower($answer) == '<any>')
                $answer = '';
            }
          }
          $this->view->attachment = is_null($responseMsg['attachment']) ? false : json_decode($responseMsg['attachment'], true);
          $responseMsg = $responseMsg['funfact'];
          if ($this->orderHunt->order_id == 3037) {
            if ($responseMsg) {
              $responseMsg = 'Fun Fact: ' . $responseMsg;
            }
          }
          if (!empty($answer) || $answer === '0')
            $this->view->correct_answer = $this->view->t->_('The correct answer was:') . '<br>' . $answer . '<br><br>';
        } else {
          if ($responseMsg['customq'] == 1 && $thisOrderHunt->order_id == NcrController::ORDER_ID && $responseMsg['cscore'] == 0)
            $this->view->customNCR = true;
          $this->view->points = $responseMsg['answer_action'] == Answers::AnsweredWithHint ? floor($responseMsg['cscore'] / 2) : $responseMsg['cscore'];
          if ($responseMsg['question_type'] == QuestionTypes::Photo) {
            $this->view->image = $this->config->application->frontUploadsDir->uri . $thisOrderHunt->id . '/' . $thisTeam->id . '/' . $responseMsg['id'] . '.jpg';
            $this->view->facebookSDK = true;
          }
          $this->view->attachment = is_null($responseMsg['attachment']) ? false : json_decode($responseMsg['attachment'], true);
          $responseMsg = $responseMsg['funfact'] . (empty($responseMsg['response_correct']) ? '' : ("\r\n\r\n" . $responseMsg['response_correct']));
          if ($this->orderHunt->order_id == 3037) {
            if ($responseMsg) {
              $responseMsg = 'Fun Fact: ' . $responseMsg;
            }
          }
          /*$this->view->audio = [
            ['/files/cheers.mp3', 'audio/mpeg'],
            ['/files/cheers.wma', 'audio/wma'],
            ['/files/cheers.ogg', 'audio/ogg'],
            ['/files/cheers.wav', 'audio/wav']
          ];*/
        }
      } else {
        $responseMsg = false;
      }
    } else {
      $responseMsg = false;
    }
    if (isset($currentPosX) && !is_null($question))
      $question['currentPos'] = $currentPosX;

    $timeToStart = $startTime - $time - 2;
    $timeToEnd = strtotime($thisOrderHunt->finish) - $time;
    $cacheTime = max(60000, $timeToEnd);

    if ($isPost) {
      $action = $request->getPost('action', 'trim');

      if (!empty($responseMsg) && $action === 'funfact') {
        $q = $questions[$cp - 2];
        if (is_array($q)) {
          if ($q['customq'] == 1)
            $a = CustomAnswers::findFirstById($q['aid']);
          else
            $a = Answers::findFirstById($q['aid']);
          if ($a) {
            $a->funfact_viewed = date('Y-m-d H:i:s');
            if ($a->save()) {
              /*$lastAnswer = time();
              $responseMsg = false;*/
              return $this->response->redirect('play');
            } else {
              try {
                $this->logger->error("Funfact action error 11: (player {$this->player->id}) q:{$q['id']} msgs: " . var_export(array_map(function($m){return (string)$m;},$a->getMessages()), true));
              } catch(Exception $e) { }
            }
          } else{
            try {
              $this->logger->error("Funfact action error 12: (player {$this->player->id}) q:{$q['id']}");
            } catch(Exception $e) { }
          }
        } else {
          try {
            $this->logger->error("Funfact action error 15: (player {$this->player->id})");
          } catch(Exception $e) { }
        }
      } else if ($action === 'bonus') {
        $id = (int)$request->getPost('bqid', 'int');
        $bonusQuestion = BonusQuestions::findFirst('id=' . $id . ' AND order_hunt_id=' . $thisOrderHunt->id . ' AND winner_id IS NULL');
        if (!$bonusQuestion) {
          return $this->jsonResponse([
            'success' => false,
            'reload' => true
          ]);
        }
        $ans = $request->getPost('answer', 'trim');
        if (Answers::checkAnswer($bonusQuestion->answers, $ans)) {
          $bonusQuestion->winner_id = $this->player->id;
          $bonusQuestion->answer = $ans;
          $bonusQuestion->answer_time = date('Y-m-d H:i:s');
          if ($bonusQuestion->save()) {
            $redis->set(SB_PREFIX . 'bqanswer:' . $thisOrderHunt->id, (int)$bonusQuestion->id, 40);
            if (($fbr = $this->firebase->set(FB_PREFIX . 'bonusq/' . $thisOrderHunt->id . '/' . BonusQuestions::count('order_hunt_id=' . $thisOrderHunt->id . ' AND id < ' . $bonusQuestion->id), false, [], 5)) != 'false') {
              try {
                $this->logger->critical('Firebase bonusQuestion update failed: ' . $bonusQuestion->id . ' ' . var_export($fbr, true));
              } catch(Exception $e) { }
            }
            $redis->set(SB_PREFIX . 'bqanswer:' . $thisOrderHunt->id, (int)$bonusQuestion->id, 40);
            return $this->jsonResponse([
              'success' => true,
              'reload' => true
            ]);
          } else {
            try {
              $this->logger->critical('BonusQuestions: failed to save answer ' . var_export($bonusQuestion->toArray(), true));
            } catch(Exception $e) { }
            return $this->jsonResponse([
              'success' => false
            ]);
          }
        } else {
          return $this->jsonResponse([
            'success' => false,
            'message' => 'Wrong Answer'
          ]);
        }
      }
    } else {
      $action = false;
      if ($request->getQuery('bqa') && ($bqid = (int)$redis->get(SB_PREFIX . 'bqanswer:' . $thisOrderHunt->id)) > 0) {
        if ($bonusQuestion = BonusQuestions::findFirst('id=' . $bqid . ' AND order_hunt_id=' . $thisOrderHunt->id . ' AND winner_id IS NOT NULL')) {
          if ($bonusQuestion->winner_id == $this->player->id) {
            $this->view->showBonusQuestionBox = true;
            $this->view->bqPoints = $bonusQuestion->type == BonusQuestions::TypeTeam ? $bonusQuestion->score : false;
          } else if ($bonusQuestion->type == BonusQuestions::TypeTeam) {
            $winner = $bonusQuestion->Winner;
            $this->view->showBonusQuestionBox = $winner->team_id == $thisTeam->id ? [$winner->email, $winner->first_name, $winner->last_name] : false;
            $this->view->bqPoints = $bonusQuestion->score;
          } else {
            $this->view->showBonusQuestionBox = false;
          }
        }
      }
    }

    unset($questions);

    $isB2C = $thisOrderHunt->isB2CEnabled();

    if ($timeToStart > 0) {
      $this->view->startTimer = $timeToStart;
    } else if (is_null($question)) {
      if ($isB2C) {
        if (empty($responseMsg))
          $this->view->leaderBoardPaypal = true;
          $this->view->fullUri = $this->config->fullUri;
        if ($redis->get(SB_PREFIX . 'ohmail:' . $thisOrderHunt->id . ':' . $thisTeam->id) != 1) {
          try {
            $attachments = [];
            $files = glob($this->config->application->frontUploadsDir->path . $thisOrderHunt->id . '/' . $thisTeam->id . '/*{0,1,2,3,4,5,6,7,8,9}.jpg', GLOB_BRACE | GLOB_NOSORT);
            $a = [];
            $s = 0;
            $maxMsg = 15*1024*1024;
            foreach ($files as $f) {
              $fs = filesize($f);
              $f = '@'.$f;
              if ($s + $fs < $maxMsg) {
                $s += $fs;
                $a[] = $f;
              } else {
                if (!empty($a)) 
                  $attachments[] = $a;
                if ($fs < $maxMsg) {
                  $a = [$f];
                  $s = $fs;
                }
              }
            }
            if (count($attachments) === 0 || !empty($a))
              $attachments[] = $a;
            $loader = new \Phalcon\Loader();
            $loader->registerDirs([APP_PATH . '/apps/common/classes'])->register();
            $pe = new \BCOrderHuntPostEvent($thisOrderHunt);

            if (!$thisOrderHunt->isEmailsDisabled()) {
              foreach ($attachments as $a) {
                if ($pe->send([$this, 'sendMail'], $this->player->email, $a) === true)
                  $redis->set(SB_PREFIX . 'ohmail:' . $thisOrderHunt->id . ':' . $thisTeam->id, 1, 86400 * 14);
              }
            }
          } catch(Exception $e) { }
        }

        $this->view->end_msg = '<h2>' . $this->view->t->_('Great job!') . '</h2>' . (is_null($thisOrderHunt->end_msg) ? 'You\'ve completed your scavenger hunt! Hope you had fun today, and be sure to spread the word about Strayboots' : nl2br(htmlspecialchars($thisOrderHunt->end_msg)));
      } else {

        //$this->view->responseLink = ['Leaderboard', $this->url->get('leaderboard')];
        $this->view->end_msg = '<h2 style="margin-top:-20px; margin-bottom:20px;">' . $this->view->t->_('Great job!') . '</h2>' . (is_null($thisOrderHunt->end_msg) ? $this->view->t->_('You\'ve completed your game!<br><br>We hope you had fun today. Meet your group at your end location to hear the final results.<br><span style="color:#ccc;">(Check your email later for the leaderboard and photos).</span>') : nl2br(htmlspecialchars($thisOrderHunt->end_msg)));

      }

      $this->view->facebookSDK = true;

      $firebasePosition = empty($responseMsg) ? 99999 : '88888_0_0';
      if ($redis->get(SB_PREFIX . 'ohloc:' . $thisOrderHunt->id . ':' . $thisTeam->id) != $firebasePosition) {
        if (($fbr = str_replace('"', '', $this->firebase->set(FB_PREFIX . 'orderhuntloc/' . $thisTeam->id, $firebasePosition, [], 3))) != $firebasePosition) {
          try {
            $this->logger->critical("Firebase error3: (player {$this->player->id}) " . $fbr . ' should be ' . $firebasePosition);
          } catch(Exception $e) { }
        } else {
          $redis->set(SB_PREFIX . 'ohloc:' . $thisOrderHunt->id . ':' . $thisTeam->id, $firebasePosition, 60);
        }
      }

      if (empty($responseMsg) && !$this->isSurveyAnswered())
        return $this->response->redirect('play/survey');

      /*$responseMsg = */$showHint = false;
      //if ($responseMsg === false)
      //  $responseMsg = '';
    } else {
      $this->view->qattachment = is_null($question['qattachment']) ? false : json_decode($question['qattachment'], true);

      if ($timeToEnd < 0) {
        if ($redis->get(SB_PREFIX . 'ohloc:' . $thisOrderHunt->id . ':' . $thisTeam->id) != 99999) {
          if (($fbr = str_replace('"', '', $this->firebase->set(FB_PREFIX . 'orderhuntloc/' . $thisTeam->id, 99999, [], 3))) != 99999) {
            try {
              $this->logger->critical("Firebase error2: (player {$this->player->id}) " . $fbr);
            } catch(Exception $e) { }
          } else {
            $redis->set(SB_PREFIX . 'ohloc:' . $thisOrderHunt->id . ':' . $thisTeam->id, 99999, 60);
          }
        }
        if (!$this->isSurveyAnswered())
          return $this->response->redirect('play/survey');
        if ($thisOrderHunt->isMultiHunt())
          return $this->response->redirect('index/chooseHunt');

        $responseMsg = $showHint = false;
        if (is_null($thisOrderHunt->timeout_msg)) {
          if ($isB2C) {
            $this->view->leaderBoardPaypal = true;
            $this->view->end_msg = '<h2>This game has ended!</h2>Hope you had fun, and be sure to spread the word about Strayboots!';
          } else {
            $this->view->end_msg = '<h2>This game has ended!</h2>Meet your group at your end location to hear the official results!<br><br>Hope you had fun - be sure to spread the word about Strayboots!';
          }
        } else {
          $this->view->end_msg = '<h2>This game has ended!</h2>' . nl2br(htmlspecialchars($thisOrderHunt->timeout_msg));
        }
      } else {
        $this->view->showHint = $showHint = !defined('hideHints') && $redis->exists(SB_PREFIX . 'hint:' . $thisOrderHunt->id . ':' . $thisTeam->id . ':' . $question['id']);

        $firebasePosition = $question['currentPos'] . '_' . (int)$showHint . '_' . (empty($responseMsg) ? 1 : 0);
        if ($redis->get(SB_PREFIX . 'ohloc:' . $thisOrderHunt->id . ':' . $thisTeam->id) != $firebasePosition) {
          if (($fbr = str_replace('"', '', $this->firebase->set(FB_PREFIX . 'orderhuntloc/' . $thisTeam->id, $firebasePosition, [], 3))) != $firebasePosition) {
            try {
              $this->logger->critical("Firebase error: (player {$this->player->id}) " . $fbr . ' should be ' . $firebasePosition);
            } catch(Exception $e) { }
          } else {
            $redis->set(SB_PREFIX . 'ohloc:' . $thisOrderHunt->id . ':' . $thisTeam->id, $firebasePosition, 60);
          }
        }

        //if (empty($responseMsg) && $thisOrderHunt->id != 1623/* && !$thisOrderHunt->isMultiHunt()*/) {
        //  if ($this->config->hunt->surveyAfterQuestion === true && $thisOrderHunt->order_id != 112) {
        //      $surveyAfterQuestion = $thisOrderHunt->order_id == NcrController::ORDER_ID ? 25 : ($question['numQuestions'] - 1);
        //      if ($surveyAfterQuestion > 0 && $question['currentPos'] > $surveyAfterQuestion && !$this->isSurveyAnswered())
        //          return $this->response->redirect('play/survey');
        //  }
        //}

        if ($question['question_type'] == QuestionTypes::Timer) {
          $tkey = SB_PREFIX . 'qtimer:' . $thisTeam->id . ':' . $thisOrderHunt->id . ':' . $question['id'];
          if (!($timerTimeLeft = $redis->get($tkey))) {
            if (preg_match('/^\d{2}:\d{2}$/', $question['answers'])) {
              $timerTimeLeft = explode(':', $question['answers']);
              $timerTimeLeft = $timerTimeLeft[0] * 60 + $timerTimeLeft[1];
            } else {
              $timerTimeLeft = 1200;
            }
            $timerTimeLeft += $time;
            $redis->set($tkey, $timerTimeLeft, 86400);
          }
          $this->view->timerTimeLeft = max(0, $timerTimeLeft - $time);
        }
        
        $this->view->disableHint = (int)$question['disable_hint'];
        $this->view->disableSkip = (int)$question['disable_skip'];

        $question['timeout'] = (int)$question['timeout'];
        if ($question['timeout'] > 0) {
          if ($breakTime = $redis->get(SB_PREFIX . 'breakp:' . $thisOrderHunt->id . ':' . ($question['currentPos'] - 1)))
            $lastAnswer = max($lastAnswer, $breakTime);
          if ($strategyTime = $redis->get(SB_PREFIX . 'strategyp:' . $thisOrderHunt->id . ':' . $thisTeam->id))
            $lastAnswer = max($lastAnswer, $strategyTime);
          $timeoutLeft = $lastAnswer - $time + $question['timeout'] + 1;
          if ($timeoutLeft <= 0 && is_null($question['answer_action'])) {
            if ($question['customq'] == 1) {
              $a = new CustomAnswers();
              $a->order_hunt_id = $thisOrderHunt->id;
              $a->custom_question_id = $question['id'];
              $a->team_id = $thisTeam->id;
            } else {
              $a = new Answers();
              $a->hunt_id = $thisOrderHunt->hunt_id;
              $a->question_id = $question['id'];
            }
            $a->team_id = $thisTeam->id;
            $a->action = Answers::Skipped;
            $a->answer = null;
            if ($a->save()) {
              $redis->set(SB_PREFIX . 'qtimeout:' . $thisTeam->id . ':' . $question['id'], 1, 172800);
            } else {
              try {
                $this->logger->error('Timeout Skip failed: team:' . $thisTeam->id . ' question: ' . $question['id']);
              } catch(Exception $e) { }
              $this->flash->error('An error occurred; please try again');
            }
            return $this->response->redirect('play?re=1');
          }
          $this->view->qtimeout = [$timeoutLeft, $question['timeout']];
        }

        if ($isLeader) {
          if ($question['limitAnswers'] === '1') {
            $this->view->answerLimit = $limit = max(0, $redis->get(SB_PREFIX . 'answerslimit:' . $thisTeam->id . ':' . $question['id'])) + 1;
            if ($limit > 3) {
              $isPost = $limitSkip = true;
              $action = 'skip';
            }
          }
        }

        if ($isLeader && $isPost) {
          $qid = $request->getPost('qid', 'int');
          if ($question['id'] == $qid || isset($limitSkip)) {
            $isCustomQuestion = $question['customq'] == 1;
            if ($isCustomQuestion) {
              $a = new CustomAnswers();
              $a->order_hunt_id = $thisOrderHunt->id;
              $a->custom_question_id = $question['id'];
            } else {
              $a = new Answers();
              $a->hunt_id = $thisOrderHunt->hunt_id;
              $a->question_id = $question['id'];
            }
            $a->team_id = $thisTeam->id;

            if ($action === 'hint') {
              $redis->set(SB_PREFIX . 'hint:' . $thisOrderHunt->id . ':' . $thisTeam->id . ':' . $question['id'], 1, (int)max(259200, $cacheTime));
              return $this->response->redirect('play');
            } else if ($action === 'skip') {
              $a->action = Answers::Skipped;
              $a->answer = null;
              if ($a->save()) {
                if ($request->getPost('autoskip') === '1')
                  $redis->set(SB_PREFIX . 'qtimeout:' . $thisTeam->id . ':' . $question['id'], 1, 172800);
                return $this->response->redirect('play?re=2');
              } else {
                try {
                  $this->logger->error('Skip failed: team:' . $thisTeam->id . ' question: ' . $question['id']);
                } catch(Exception $e) { }
                $this->flash->error('Skip failed; please try again');
              }
            } else if ($action === 'answer') {
              if ($question['question_type'] == QuestionTypes::Text) {
                $answer = $request->getPost('answer');
                if (Answers::checkAnswer($question['answers'], $answer)) {
                  $a->action = $showHint ? Answers::AnsweredWithHint : Answers::Answered;
                  $a->answer = $answer;
                  if ($a->save()) {
                    return $this->response->redirect('play');
                  } else {
                    try {
                      $this->logger->error('Answer failed: team:' . $thisTeam->id . ' question: ' . $question['id'] . ' answer: ' . $answer . ' msgs: ' . var_export(array_map(function($m){return (string)$m;},$a->getMessages()), true));
                    } catch(Exception $e) { }
                    $this->flash->error('Failed; please try again');
                  }
                } else {
                  $this->wrongAnswer($question, $showHint, $answer);
                  return $this->response->redirect('play');
                }
              } else if ($question['question_type'] == QuestionTypes::OpenText) {
                $answer = $request->getPost('answer');
                $a->action = $showHint ? Answers::AnsweredWithHint : Answers::Answered;
                $a->answer = $answer;
                if ($a->save()) {
                  return $this->response->redirect('play');
                } else {
                  try {
                    $this->logger->error('Answer failed: team:' . $thisTeam->id . ' question: ' . $question['id'] . ' answer: ' . $answer . ' msgs: ' . var_export(array_map(function($m){return (string)$m;},$a->getMessages()), true));
                  } catch(Exception $e) { }
                  $this->flash->error('Failed; please try again');
                }
              } else if ($question['question_type'] == QuestionTypes::Photo) {
                $photoSuccess = false;
                $uploadPath = $this->config->application->frontUploadsDir->path . $thisOrderHunt->id . '/' . $thisTeam->id . '/';
                $filePath = $uploadPath . ($isCustomQuestion ? '00' : '') . $question['id'] . '.jpg';
                if (file_exists($uploadPath) || mkdir($uploadPath, 0777, true)) {
                  if (file_exists($filePath)) {
                    @unlink($filePath);
                    try {
                      $this->logger->warning('Upload: file exists. team:' . $thisTeam->id . ' question: ' . $question['id']);
                    } catch(Exception $e) { }
                  }
                  try {
                    if ($request->hasFiles()) {
                      $file = $request->getUploadedFiles()[0];
                      if ($tmp = $file->getTempName()) {
                        $imageMimeCheck = preg_match('/^image\//i', $file->getRealType());
                        $suffix = $file->getExtension();
                        $imageExtensionCheck = in_array(strtolower($suffix), ['jpg', 'jpeg', 'gif', 'png']);
                        if ($imageMimeCheck && $imageExtensionCheck && ($img_info = getimagesize($tmp)) !== false) {
                          $src = false;
                          switch ($img_info[2]) {
                            case IMAGETYPE_GIF: $src = imagecreatefromgif($tmp); break;
                            case IMAGETYPE_PNG: $src = imagecreatefrompng($tmp); break;
                            case IMAGETYPE_JPEG:
                            case IMAGETYPE_JPEG2000: $src = 0; break;
                            default:
                          }
                          if ($src !== false) {
                            if ($src) {
                              $tmp = imagecreatetruecolor($img_info[0], $img_info[1]);
                              imagecopyresampled($tmp, $src, 0, 0, 0, 0, $img_info[0], $img_info[1], $img_info[0], $img_info[1]);
                              imagejpeg($tmp, $filePath, 85);
                              imagedestroy($tmp);
                            } else if (is_array($exif = @exif_read_data($tmp)) && !empty($exif['Orientation'])) {
                              $tmp = imagecreatefromjpeg($tmp);
                              switch($exif['Orientation']) {
                                case 8:
                                  $tmp = imagerotate($tmp, 90, 0);
                                  break;
                                case 3:
                                  $tmp = imagerotate($tmp, 180, 0);
                                  break;
                                case 6:
                                  $tmp = imagerotate($tmp, -90, 0);
                                  break;
                              }
                              imagejpeg($tmp, $filePath, 85);
                              imagedestroy($tmp);
                            } else {
                              $file->moveTo($filePath);
                            }
                            $this->jpegtran($filePath, true);
                            if (file_exists($filePath) && getimagesize($filePath) !== false) {
                              $photoSuccess = true;
                            } else {
                              try {
                                $this->logger->error('Upload error: team:' . $thisTeam->id . ' question: ' . $question['id']);
                              } catch(Exception $e) { }
                            }
                          }
                        }
                      }
                    }
                  } catch (Exception $e) {}
                  if ($photoSuccess) {
                    $a->action = $showHint ? Answers::AnsweredWithHint : Answers::Answered;
                    $a->answer = null;
                    if ($a->save()) {
                      return $this->response->redirect('play');
                    } else {
                      try {
                        $this->logger->error('Answer failed7: team:' . $thisTeam->id . ' question: ' . $question['id'] . ' answer: ' . $answer . ' msgs: ' . var_export(array_map(function($m){return (string)$m;},$a->getMessages()), true));
                      } catch(Exception $e) { }
                      $this->flash->error('Failed; please try again');
                      if (file_exists($filePath))
                        @unlink($filePath);
                    }
                  } else {
                    $this->flash->error('Please upload a valid image');         
                  }
                } else {
                  $this->flash->error('Failed to upload file; please try again');
                  try {
                    $this->logger->error('Upload: failed to create directory team:' . $thisTeam->id . ' question: ' . $question['id']);
                  } catch(Exception $e) { }
                }
              } else if ($question['question_type'] == QuestionTypes::Completion) {
                $json = json_decode($question['answers'], true);
                $answer = $request->getPost('answer', 'trim');
                if (is_array($json) && !empty($json['w']) && Answers::filterAnswer(str_replace(' ', '', $json['w'])) === Answers::filterAnswer(str_replace(' ', '', $answer))) {
                  $a->action = $showHint ? Answers::AnsweredWithHint : Answers::Answered;
                  $a->answer = $answer;
                  if ($a->save()) {
                    return $this->response->redirect('play');
                  } else {
                    try {
                      $this->logger->error('Completion failed: team:' . $thisTeam->id . ' question: ' . $question['id']);
                    } catch(Exception $e) { }
                    $this->flash->error('Failed; please try again');
                  }
                } else {
                  $this->wrongAnswer($question, $showHint, $answer);
                  return $this->response->redirect('play');
                }
              } else if ($question['question_type'] == QuestionTypes::Other) {
                $a->action = Answers::Answered;
                $a->answer = null;
                if ($a->save()) {
                  return $this->response->redirect('play');
                } else {
                  try {
                    $this->logger->error('Other failed: team:' . $thisTeam->id . ' question: ' . $question['id']);
                  } catch(Exception $e) { }
                  $this->flash->error('Failed; please try again');
                }
              } else if ($question['question_type'] == QuestionTypes::Choose) {
                $answer = (int)$request->getPost('answer') - 1;
                $answers = explode("\n", $question['answers']);
                if (isset($answers[$answer])) {
                  if (substr($answers[$answer], 0, 1) === '*') {
                    $a->action = $showHint ? Answers::AnsweredWithHint : Answers::Answered;
                    $a->answer = $answers[$answer];
                    if ($a->save()) {
                      return $this->response->redirect('play');
                    } else {
                      try {
                        $this->logger->error('Answer failed3: team:' . $thisTeam->id . ' question: ' . $question['id'] . ' answer: ' . $answer . ' msgs: ' . var_export(array_map(function($m){return (string)$m;},$a->getMessages()), true));
                      } catch(Exception $e) { }
                      $this->flash->error('Failed; please try again');
                    }
                  } else {
                    $this->wrongAnswer($question, $showHint, $answers[$answer], false);
                    $a->action = Answers::Skipped;
                    $a->answer = null;
                    if ($a->save()) {
                      $redis->set(SB_PREFIX . 'bqchooseskip:' . $thisTeam->id . ':' . $question['id'], 1, 172800);
                    } else {
                      $this->flash->error('Failed; please try again');
                      try {
                        $this->logger->error('Answer failed4: team:' . $thisTeam->id . ' question: ' . $question['id'] . ' answer: ' . $answer . ' msgs: ' . var_export(array_map(function($m){return (string)$m;},$a->getMessages()), true));
                      } catch(Exception $e) { }
                    }
                    return $this->response->redirect('play');
                  }
                } else {
                  $this->flash->error('Failed; please try again');
                }
              }  else if ($question['question_type'] == QuestionTypes::OpenCheckbox) {
                $answers = explode("\n", $question['answers']);
                $answer = $request->getPost('answer');
                if (!is_array($answer))
                  $answer = [$answer];
                $ok = true;
                $answer = trim(implode("\n", array_map(function($ans) use (&$ok, $answers){
                  $ans = (int)$ans - 1;
                  if (isset($answers[$ans]))
                    return $answers[$ans];
                  $ok = false;
                  return null;
                }, $answer)));
                if ($ok && $answer !== '') {
                  $a->action = $showHint ? Answers::AnsweredWithHint : Answers::Answered;
                  $a->answer = $answer;
                  if ($a->save()) {
                    return $this->response->redirect('play');
                  } else {
                    try {
                      $this->logger->error('Answer failed9: team:' . $thisTeam->id . ' question: ' . $question['id'] . ' answer: ' . $answer . ' msgs: ' . var_export(array_map(function($m){return (string)$m;},$a->getMessages()), true));
                    } catch(Exception $e) { }
                    $this->flash->error('Failed; please try again');
                  }
                } else {
                  $this->flash->error('Please choose an answer');
                }
              } else if ($question['question_type'] == QuestionTypes::Timer) {
                if ($this->view->timerTimeLeft == 0) {
                  $a->action = Answers::Answered;
                  $a->answer = null;
                  if ($a->save()) {
                    return $this->response->redirect('play');
                  } else {
                    try {
                      $this->logger->error('Other failed: team:' . $thisTeam->id . ' question: ' . $question['id']);
                    } catch(Exception $e) { }
                    $this->flash->error('Failed; please try again');
                  }
                } else {
                  $this->flash->error('Please wait untill the timer is finished');
                }
              } else {
                try {
                  $this->logger->error('Unknown question type: team:' . $thisTeam->id . ' question: ' . $question['id']);
                } catch(Exception $e) { }
                $this->flash->error('Failed; please try again');
              }
            } else {
              try {
                $this->logger->error('Unknown action: team:' . $thisTeam->id . ' question: ' . $question['id']);
              } catch(Exception $e) { }
              $this->flash->error('Failed; please try again');
            }
          } else {
            $isPost = false;
          }
        }

        $this->view->question = $question;
      }

      if ($question['question_type'] == QuestionTypes::Completion) {
        $json = json_decode($question['answers'], true);
        if (is_array($json) && !empty($json['w']) && is_array($json['l'])) {
          $word = str_split($json['w']);
          foreach ($json['l'] as $k)
            $word[$k] = null;
          $this->view->completion = $word;
        }
      }
    }

    if ($timeToStart <= 0) {
      if ($responseMsg/* && !($isLeader && ($showHint || $isPost))*/) {
        $this->view->response_msg = nl2br(htmlspecialchars($responseMsg));
        if ($lastSkip) {
          if ($lastSkip === 3)
            $titles = ['Time\'s Up'];
          else if ($lastSkip === 2)
            $titles = ['Sorry, wrong answer'];
          else
            $titles = ['Aw, you skipped it!'];
        } else {
          $titles = ['Great!'];
        }
        $this->view->response_msg_title = $this->view->t->_($titles[array_rand($titles)]);
        if (is_null($question))
          $this->view->responseAck = 1;
        $this->view->pick('play/message');
      }

      if (isset($this->view->end_msg)) {

        $frontUploadsDir = $this->config->application->frontUploadsDir;
        $files = glob($frontUploadsDir->path . $thisOrderHunt->id . '/' . $thisTeam->id . '/*{0,1,2,3,4,5,6,7,8,9}.jpg', GLOB_BRACE | GLOB_NOSORT);
        if (!empty($files)) {
          usort($files, function($a, $b){
            return filemtime($a) - filemtime($b);
          });
          $this->view->image = $frontUploadsDir->uri . substr($files[0], strlen($frontUploadsDir->path));
        }

        if ($this->view->multiHunt = $thisOrderHunt->isMultiHunt()) {
          $now = date('Y-m-d H:i:s');
          $this->view->lastMulti = 0 == $this->db->fetchColumn(<<<EOF
SELECT count(1) FROM (
  SELECT oh.id, (SUM(!ISNULL(a.id)) * 100 / (SELECT COUNT(1) FROM hunt_points hp WHERE hp.hunt_id=oh.hunt_id)) AS answered
  FROM `order_hunts` oh
  LEFT JOIN answers a ON (a.team_id = {$thisTeam->id} AND a.hunt_id = oh.hunt_id)
  WHERE oh.order_id = {$thisOrderHunt->order_id}
  AND oh.finish > '{$now}' AND oh.expire > '{$now}' AND oh.flags & 4 = 0 AND oh.flags & 8 = 8
  GROUP BY oh.id
) x WHERE x.answered < 100
EOF
          );
          /*if ($this->view->lastMulti && !$this->isSurveyAnswered())
            $this->setSurveyInfo();
        } /*else if (!$this->isSurveyAnswered()) {
          $this->setSurveyInfo();*/
        }
        $this->view->pick('play/message');
      }

      $this->view->firebase = [
        'config' => $this->config->firebase,
        'appLoc' => [
          (int)$thisTeam->id, is_null($question) ? (empty($responseMsg) ? 99999 : '88888_0_0') : $question['currentPos'], $showHint, empty($responseMsg),
          'orderHunt' => (int)$thisOrderHunt->id,
          'orderId' => (int)$thisOrderHunt->order_id,
          'timeLeft' => isset($this->view->end_msg) && empty($responseMsg) ? -1 : $timeToEnd
        ]
      ];
    }

    $this->view->orderHunt = $thisOrderHunt;
    $this->view->orderHuntId = $thisOrderHunt->id;

    $this->assets->collection('style')->addCss('/css/app/play.css');
    $this->assets->collection('script')
              ->addJs('/js/plugins/js.cookie.min.js')
              ->addJs('/js/app/play.js');

  }

  public function surveyAction()
  {
    if ($this->requirePlayer())
      return true;

    if ($this->isSurveyAnswered())
      return $this->response->redirect('play');

    $this->view->surveyId = $this->orderHunt->survey_id;
    $this->view->end_msg = true;
    if ($this->request->getQuery('completed') === 'y') {
      if ($this->request->getQuery('skip') === 'y') {
        try {
          $this->logger->info("Survey Skip: player {$this->player->id} orderHunt: " . $this->orderHunt->id);
        } catch(Exception $e) { }
      }
      $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
      $this->view->completed = true;
      $this->view->playerId = $this->player->id;
      $this->redis->set(SB_PREFIX . 'survey:' . $this->orderHunt->id . ':' . $this->player->id, 1, 86400 * 3);
      $this->redis->delete(SB_PREFIX . 'survey:' . $this->orderHunt->id . ':api');
    } else {
      $this->view->completed = false;
    }

    $this->setSurveyInfo();
    $this->view->firebase = [
      'config' => $this->config->firebase,
      'appLoc' => [
        (int)$this->team->id,
        'orderHunt' => (int)$this->orderHunt->id,
        'orderId' => (int)$thisOrderHunt->order_id,
        'timeLeft' => max(-1, strtotime($this->orderHunt->finish) - time())
      ]
    ];
    $this->assets->collection('style')->addCss('/css/app/play.css');
  }

  private function setSurveyInfo()
  {
    $this->view->surveyInfo = [
      $this->player->first_name,
      $this->player->last_name,
      $this->player->email,
      json_encode([
        '11' => $this->orderHunt->Order->name . ' / ' . $this->hunt->name,
        '12' => $this->orderHunt->id,
        '13' => $this->player->isLeader(),
        '14' => $this->player->id
      ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
      $this->orderHunt->id
    ];
  }

  private function isSurveyAnswered()
  {
    return SBENV === 'europe' || $this->orderHunt->isSurveyDisabled() ||
        $this->cookies->has('survey' . $this->orderHunt->id . 'p' . $this->player->id) ||
        $this->redis->exists(SB_PREFIX . 'survey:' . $this->orderHunt->id . ':' . $this->player->id);
  }

  private function wrongAnswer($question, $hint = false, $answer = '', $flash = true)
  {
    if ($question['limitAnswers'] === '1') {
      $this->view->answerLimit = $this->redis->incr(SB_PREFIX . 'answerslimit:' . $this->team->id . ':' . $question['id']);
      $this->redis->setTimeout(SB_PREFIX . 'answerslimit:' . $this->team->id . ':' . $question['id'], 172800);
    }

    if ($flash)
      $this->flash->error($this->view->t->_('Wrong answer')); // TODO change this

    if (empty($answer))
      return;

    if ($question['customq'] == 1) {
      try {
        $this->logger->warning('Wrong custom answer. orderHunt: ' . $this->orderHunt->id . ' customQuestion: ' . $question['id'] . ' player: ' . $this->player->id . ' (' . $this->player->email . '): ' . $answer);
      } catch(Exception $e) { }
      return;
    }

    $wrongAnswer = new \WrongAnswers();
    $wrongAnswer->order_hunt_id = $this->orderHunt->id;
    $wrongAnswer->player_id = $this->player->id;
    $wrongAnswer->question_id = $question['id'];
    $wrongAnswer->answer = $answer;
    $wrongAnswer->hint = $hint ? 1 : 0;

    if (!$wrongAnswer->save()) {
      try {
        $msg = var_export(array_map(function($m){
          return (string)$m;
        }, $wrongAnswer->getMessages()), true);
        $this->logger->error('Failed to save wrong answer: team:' . $this->team->id . ' question: ' . $question['id'] . ' answer: ' . $answer . ' msgs: ' . $msg);
      } catch(Exception $e) { }
    }
  }

}

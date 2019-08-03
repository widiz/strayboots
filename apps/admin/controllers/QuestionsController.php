<?php

namespace Play\Admin\Controllers;

use \Questions,
  \QuestionTypes,
  \QuestionTags,
  \PDO,
  DataTables\DataTable,
  Phalcon\Mvc\Model\Transaction\Failed as TxFailed,
  Phalcon\Mvc\Model\Transaction\Manager as TxManager;

class QuestionsController extends \ControllerBase
{
  /**
   * Index action
   */
  public function indexAction()
  {
    if ($this->requireUser())
      return true;

    $questionTypes = QuestionTypes::find()->toArray();
    $questionTypes = array_combine(array_map(function($c){
      return $c['id'];
    }, $questionTypes), array_map(function($c){
      return $c['name'];
    }, $questionTypes));
    $this->view->questionTypes = $questionTypes;

    if ($tag = (int)$this->request->getQuery('tag', 'int')) {
      if ($tag = \Tags::findFirstById($tag))
        $this->view->tag = $tag->tag;
    }

    $this->assets->collection('script')
        ->addJs('/template/js/plugins/dataTables/datatables.min.js')
        ->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
        ->addJs('/js/admin/questions.js');
    $this->assets->collection('style')
        ->addCss('/template/css/plugins/dataTables/datatables.min.css')
        ->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
  }

  public function datatableAction()
  {
    if ($this->requireUser())
      throw new \Exception(403, 403);

    /*$builder = $this->modelsManager->createBuilder()
              ->columns(
                'q.id, q.question, q.type_id, q.score, p.id as pid, p.name as pname, ' .
                '(SELECT COUNT(1) FROM \Answers a WHERE a.question_id=q.id) AS answers, ' .
                '(SELECT COUNT(1) FROM \HuntPoints hp WHERE hp.point_id=q.id) AS hunt_points'
              )
              ->from(['q' => 'Questions'])
              ->leftJoin('Points', 'p.id = q.point_id', 'p');*/
    $builder = $this->modelsManager->createBuilder()
              ->columns(
                'q.id, q.question, q.type_id, q.score, q.point_id, p.name, ' .
                '(SELECT COUNT(1) FROM \Answers a WHERE a.question_id=q.id) AS answers, ' .
                'COUNT(wa.question_id) AS wrong_answers, ' .
                '(SELECT COUNT(1) FROM \HuntPoints hp WHERE hp.question_id=q.id) AS hunt_points, ' .
                'GROUP_CONCAT(DISTINCT t.tag) AS tags, t.tag'
              )
              ->from(['q' => 'Questions'])
              ->leftJoin('WrongAnswers', 'wa.question_id = q.id','wa')
              ->leftJoin('Points', 'p.id = q.point_id','p')
              ->leftJoin('QuestionTags', 'qt.question_id = q.id','qt')
              ->leftJoin('Tags', 'qt.tag_id = t.id','t');

    if ($tag = (int)$this->request->getQuery('tag', 'int'))
      $builder->leftJoin('QuestionTags', 'q.id = qt.question_id AND qt.tag_id = ' . $tag,'qt')->where('qt.id IS NOT NULL');

    $builder->groupBy('q.id');

    $dataTables = new DataTable();
    $dataTables->fromBuilder($builder)->sendResponse();
    exit;
  }

  public function datatableWrongAction($question)
  {
    if ($this->requireUser())
      throw new \Exception(403, 403);

    $builder = $this->modelsManager->createBuilder()
              ->columns('wa.id, oh.order_id, oh.hunt_id, h.name as huntname, o.name as ordername, wa.answer, wa.created')
              ->from(['wa' => 'WrongAnswers'])
              ->leftJoin('OrderHunts', 'oh.id = wa.order_hunt_id','oh')
              ->leftJoin('Orders', 'oh.order_id = o.id', 'o')
              ->leftJoin('Hunts', 'oh.hunt_id = h.id', 'h')
              ->where('wa.question_id = ' . (int)$question);
    $dataTables = new DataTable();
    $dataTables->fromBuilder($builder)->sendResponse();
    exit;
  }

  public function datatableSubmittionAction($question)
  {
    if ($this->requireUser())
      throw new \Exception(403, 403);

    $builder = $this->modelsManager->createBuilder()
              ->columns('a.id, t.name, a.answer, a.created')
              ->from(['a' => 'Answers'])
              ->leftJoin('Teams', 't.id = a.team_id','t')
              ->where('a.question_id = ' . (int)$question);
    $dataTables = new DataTable();
    $dataTables->fromBuilder($builder)->sendResponse();
    exit;
  }

  /**
   * Displays the creation form
   */
  public function newAction()
  {
    if ($this->requireUser())
      return true;

    $this->view->vimeo = true;
    
    $this->assets->collection('script')
        ->addJs('/template/js/plugins/select2/select2.full.min.js')
        ->addJs('/template/js/plugins/moment/moment.min.js')
        ->addJs('/template/js/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js')
        ->addJs('/js/admin/questions.addedit.js');
    $this->assets->collection('style')
        ->addCss('/template/css/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css')
        ->addCss('/template/css/plugins/select2/select2.min.css');
  }

  /**
   * Duplicate a question
   */
  public function duplicateAction($id)
  {
    if ($this->requireUser())
      return true;

    $question = Questions::findFirstByid($id);
    if (!$question) {
      $this->flash->error('Question was not found');

      $this->response->redirect('questions');

      return;
    }

    $question = $question->toArray();
    $question['question'] = 'Copy of ' . $question['question'];
    unset($question['id']);

    $dup = new Questions();

    if ($dup->create($question)) {

      $this->flash->success('Question was duplicated successfully');

      $this->response->redirect('questions/edit/' . $dup->id);

    } else {

      foreach ($dup->getMessages() as $message)
        $this->flash->error($message);

      $this->response->redirect('questions');
    }
  }

  /**
   * View wrong answers for a question
   *
   * @param string $id
   */
  public function wrongAction($id)
  {
    if ($this->requireUser())
      return true;

    $question = Questions::findFirstByid($id);
    if (!$question) {
      $this->flash->error('Question was not found');

      $this->response->redirect('questions');

      return;
    }
    $this->view->question = $question;


    $this->assets->collection('script')
        ->addJs('/template/js/plugins/dataTables/datatables.min.js')
        ->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
        ->addJs('/js/admin/questions.wrong.js');
    $this->assets->collection('style')
        ->addCss('/template/css/plugins/dataTables/datatables.min.css')
        ->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
  }

  /**
   * View דubmittions answers for a question
   *
   * @param string $id
   */
  public function submittionAction($id)
  {
    if ($this->requireUser())
      return true;

    $question = Questions::findFirstByid($id);
    if (!$question) {
      $this->flash->error('Question was not found');

      $this->response->redirect('questions');

      return;
    }
    $this->view->question = $question;


    $this->assets->collection('script')
        ->addJs('/template/js/plugins/dataTables/datatables.min.js')
        ->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
        ->addJs('/js/admin/questions.submittion.js');
    $this->assets->collection('style')
        ->addCss('/template/css/plugins/dataTables/datatables.min.css')
        ->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
  }

  /**
   * Edits a question
   *
   * @param string $id
   */
  public function editAction($id)
  {
    if ($this->requireUser())
      return true;

    $question = Questions::findFirstByid($id);
    if (!$question) {
      $this->flash->error('Question was not found');

      $this->response->redirect('questions');

      return;
    }

    $this->view->id = $question->id;

    $this->view->vimeo = true;
    
    if (!$this->request->isPost()) {

      $this->tag->setDefault('id', $question->id);
      $this->tag->setDefault('point_id', $question->point_id);
      $this->tag->setDefault('type_id', $question->type_id);
      $this->tag->setDefault('name', $question->name);
      $this->tag->setDefault('score', $question->score);
      $this->tag->setDefault('question', $question->question);
      $this->tag->setDefault('qattachment', $question->qattachment);
      $this->tag->setDefault('hint', $question->hint);
      $this->tag->setDefault('disable_skip', $question->disable_skip);
      $this->tag->setDefault('disable_hint', $question->disable_hint);
      $this->tag->setDefault('funfact', $question->funfact);
      $this->tag->setDefault('response_correct', $question->response_correct);
      $this->tag->setDefault('answers', in_array($question->QuestionType->type, [QuestionTypes::Choose, QuestionTypes::OpenCheckbox]) ? str_replace("\n", "\r\n", $question->answers) : $question->answers);
      $this->tag->setDefault('attachment', $question->attachment);
      $this->tag->setDefault('timeout', is_null($question->timeout) ? '' : gmdate('H:i:s', $question->timeout));
      $this->tag->setDefault('tags', $this->db->fetchColumn('SELECT GROUP_CONCAT(tag_id) FROM question_tags WHERE question_id=' . (int)$question->id));
    }

    $this->assets->collection('script')
        ->addJs('/template/js/plugins/select2/select2.full.min.js')
        ->addJs('/template/js/plugins/moment/moment.min.js')
        ->addJs('/template/js/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js')
        ->addJs('/js/admin/questions.addedit.js');
    $this->assets->collection('style')
        ->addCss('/template/css/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css')
        ->addCss('/template/css/plugins/select2/select2.min.css');

  }

  /**
   * Creates a new question
   */
  public function createAction()
  {
    if ($this->requireUser())
      return true;
    if (!$this->request->isPost()) {
      $this->response->redirect('questions');

      return;
    }

    $question = new Questions();
    $question->point_id = $this->request->getPost('point_id', 'int');
    $question->type_id = $this->request->getPost('type_id', 'int');
    $question->name = $this->request->getPost('name', 'trim');
    $question->score = $this->request->getPost('score', 'int');
    $question->question = $this->request->getPost('question', 'trim');
    $question->hint = $this->request->getPost('hint', 'trim');
    $question->funfact = $this->request->getPost('funfact', 'trim');
    $question->response_correct = $this->request->getPost('response_correct', 'trim');
    $question->answers = $this->request->getPost('answers');
    $question->timeout = $this->request->getPost('timeout', 'trim');
    $question->disable_skip = (int)$this->request->getPost('disable_skip');
    $question->disable_hint = (int)$this->request->getPost('disable_hint');
    if (empty($question->point_id))
      $question->point_id = null;
    if (empty($question->score))
      $question->score = null;
    if (empty($question->timeout))
      $question->timeout = null;
    if (empty($question->name))
      $question->name = null;

    switch ($this->request->getPost('at2')) {
      case 'youtube':
        $question->qattachment = json_encode([
          'type' => Questions::ATTACHMENT_YOUTUBE,
          'video' => trim($this->request->getPost('youtube2', 'string'))
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        break;
      case 'vimeo':
        $question->qattachment = json_encode([
          'type' => Questions::ATTACHMENT_VIMEO,
          'video' => (int)$this->request->getPost('vimeo2', 'int')
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        break;
      case 'photo':
        $qattachment = $this->request->getPost('qattachment');
        if (!empty($photo = $this->upload('img2')) || empty($qattachment)) {
          $question->qattachment = json_encode([
            'type' => Questions::ATTACHMENT_PHOTO,
            'photo' => $photo
          ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
          $question->qattachment = $qattachment;
        }
        break;
      default:
        $question->qattachment = null;
    }

    switch ($this->request->getPost('at1')) {
      case 'youtube':
        $question->attachment = json_encode([
          'type' => Questions::ATTACHMENT_YOUTUBE,
          'video' => trim($this->request->getPost('youtube', 'string'))
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        break;
      case 'vimeo':
        $question->attachment = json_encode([
          'type' => Questions::ATTACHMENT_VIMEO,
          'video' => (int)$this->request->getPost('vimeo', 'int')
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        break;
      case 'photo':
        $attachment = $this->request->getPost('attachment');
        if (!empty($photo = $this->upload('img1')) || empty($attachment)) {
          $question->attachment = json_encode([
            'type' => Questions::ATTACHMENT_PHOTO,
            'photo' => $photo
          ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
          $question->attachment = $attachment;
        }
        break;
      default:
        $question->attachment = null;
    }

    if (!$question->save()) {
      $this->tag->setDefault('attachment', $question->attachment);
      $this->tag->setDefault('qattachment', $question->qattachment);

      foreach ($question->getMessages() as $message)
        $this->flash->error($message);

      $this->dispatcher->forward([
        'controller' => 'questions',
        'action' => 'new'
      ]);

      return;
    }

    $tags = explode(',', $this->request->getPost('tags', 'trim'));
    foreach ($tags as $tag) {
      if (!($tag > 0)) continue;
      $t = new QuestionTags();
      $t->question_id = $question->id;
      $t->tag_id = $tag;
      if (!$t->save())
        $this->flash->error('Failed to save question tag');
    }

    $this->flash->success('Question was created successfully');

    $this->response->redirect('questions');

  }

  /**
   * upload an image
   */
  private function upload($fname)
  {
    $photo = '';
    if ($this->request->hasFiles()) {
      $uploadUri = $this->config->application->frontUploadsDir->uri . 'ff/';
      $uploadPath = $this->config->application->frontUploadsDir->path . 'ff/';
      $allowedEx = ['jpg', 'jpeg', 'gif', 'png'];
      $mt = round(microtime(1), 2) * 100 % 1e8;
      $heightLimit = 1200;
      $widthLimit = 1600;
      foreach ($this->request->getUploadedFiles() as $file) {
        if ($file->getKey() != $fname)
          continue;
        if ($tmp = $file->getTempName()) {
          $imageMimeCheck = preg_match('/^image\//i', $file->getRealType());
          $suffix = $file->getExtension();
          $imageExtensionCheck = in_array(strtolower($suffix), $allowedEx);
          if ($imageMimeCheck && $imageExtensionCheck && ($img_info = getimagesize($tmp)) !== false) {
            try {
              $type = '';
              $src = false;
              switch ($img_info[2]) {
                case IMAGETYPE_GIF: $type = 'gif'; $src = imagecreatefromgif($tmp); break;
                case IMAGETYPE_PNG: $type = 'png'; $src = imagecreatefrompng($tmp); break;
                case IMAGETYPE_JPEG:
                case IMAGETYPE_JPEG2000: $type = 'jpg'; $src = imagecreatefromjpeg($tmp); break;
                default:
              }
              if ($src !== false) {
                do {
                  $bname = $mt . mt_rand(1, 1e5) . '.' . $type;
                  $filePath = $uploadPath . $bname;
                } while (file_exists($filePath));

                if ($img_info[2] == IMAGETYPE_GIF) {
                  $file->moveTo($filePath);
                  if (file_exists($filePath) && getimagesize($filePath) !== false) {
                    $photo = $uploadUri . $bname;
                    break;
                  }
                }

                $width = $img_info[0];
                $height = $img_info[1];

                if ($width > $widthLimit || $height > $heightLimit) {
                  $ratio = min($widthLimit / $width, $heightLimit / $height);
                  $width = round($width * $ratio);
                  $height = round($height * $ratio);
                }
                
                $image = imagecreatetruecolor($width, $height);
                imagecopyresampled($image, $src, 0, 0, 0, 0, $width, $height, $img_info[0], $img_info[1]);

                if ($type == 'jpg' && is_array($exif = @exif_read_data($tmp)) && !empty($exif['Orientation'])) {
                  switch($exif['Orientation']) {
                    case 8:
                      $image = imagerotate($image, 90, 0);
                      break;
                    case 3:
                      $image = imagerotate($image, 180, 0);
                      break;
                    case 6:
                      $image = imagerotate($image, -90, 0);
                      break;
                  }
                }

                switch($type){
                  case 'gif': imagegif($image, $filePath); break;
                  case 'jpg': imagejpeg($image, $filePath, 85); break;
                  case 'png': imagepng($image, $filePath, 0); break;
                  default:
                    continue;
                }
                
                imagedestroy($image);

                if ($type == 'jpg') {
                  if (!empty($err = $this->jpegtran($filePath, true))) {
                    try {
                      $this->logger->error('jpegtran failed on "' . $filePath . '" ' . $err);
                    } catch(Exception $e) { }
                  }
                } else if ($type == 'png') {
                  if (!empty($err = $this->pngquant($filePath))) {
                    try {
                      $this->logger->error('pngquant failed on "' . $filePath . '" ' . $err);
                    } catch(Exception $e) { }
                  }
                }

                if (file_exists($filePath) && getimagesize($filePath) !== false) {
                  $photo = $uploadUri . $bname;
                  break;
                }
              }
            } catch (Exception $e) {}
          }
        }
      }
    }

    return $photo;
  }

  /**
   * Add answer to question
   */
  public function addAnswerAction()
  {
    if ($this->requireUser())
      return true;

    $id = (int)$this->request->getPost('id', 'int');
    $answer = $this->request->getPost('answer', 'trim');
    $wrongAnswer = \WrongAnswers::findFirstByid($id);
    $question = $wrongAnswer ? $wrongAnswer->Question : false;

    if (!$question || $question->QuestionType->type != QuestionTypes::Text || \Answers::checkAnswer($question->answers, $wrongAnswer->answer)) {
      return $this->jsonResponse([
        'success' => false
      ]);
    }

    $question->answers = trim($question->answers) . "\n" . $wrongAnswer->answer;

    return $this->jsonResponse([
      'success' => $question->save()
    ]);
  }


  /**
   * Saves a question edited
   *
   */
  public function saveAction()
  {
    if ($this->requireUser())
      return true;

    if (!$this->request->isPost()) {
      $this->response->redirect('questions');

      return;
    }

    $id = (int)$this->request->getPost('id', 'int');
    $question = Questions::findFirstByid($id);

    if (!$question) {
      $this->flash->error('question Qoes not exist ' . $id);

      $this->response->redirect('questions');

      return;
    }

    $question->point_id = $this->request->getPost('point_id', 'int');
    $question->type_id = $this->request->getPost('type_id', 'int');
    $question->name = $this->request->getPost('name', 'trim');
    $question->score = $this->request->getPost('score', 'int');
    $question->question = $this->request->getPost('question', 'trim');
    $question->hint = $this->request->getPost('hint', 'trim');
    $question->funfact = $this->request->getPost('funfact', 'trim');
    $question->response_correct = $this->request->getPost('response_correct', 'trim');
    $question->answers = $this->request->getPost('answers');
    $question->timeout = $this->request->getPost('timeout', 'trim');
    $question->disable_skip = (int)$this->request->getPost('disable_skip');
    $question->disable_hint = (int)$this->request->getPost('disable_hint');
    if (empty($question->point_id))
      $question->point_id = null;
    if (empty($question->score))
      $question->score = null;
    if (empty($question->timeout))
      $question->timeout = null;
    if (empty($question->name))
      $question->name = null;

    switch ($this->request->getPost('at2')) {
      case 'youtube':
        $question->qattachment = json_encode([
          'type' => Questions::ATTACHMENT_YOUTUBE,
          'video' => trim($this->request->getPost('youtube2', 'string'))
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        break;
      case 'vimeo':
        $question->qattachment = json_encode([
          'type' => Questions::ATTACHMENT_VIMEO,
          'video' => (int)$this->request->getPost('vimeo2', 'int')
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        break;
      case 'photo':
        $qattachment = $this->request->getPost('qattachment');
        if (!empty($photo = $this->upload('img2')) || empty($qattachment)) {
          $question->qattachment = json_encode([
            'type' => Questions::ATTACHMENT_PHOTO,
            'photo' => $photo
          ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
          $question->qattachment = $qattachment;
        }
        break;
      default:
        $question->qattachment = null;
    }

    switch ($this->request->getPost('at1')) {
      case 'youtube':
        $question->attachment = json_encode([
          'type' => Questions::ATTACHMENT_YOUTUBE,
          'video' => trim($this->request->getPost('youtube', 'string'))
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        break;
      case 'vimeo':
        $question->attachment = json_encode([
          'type' => Questions::ATTACHMENT_VIMEO,
          'video' => (int)$this->request->getPost('vimeo', 'int')
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        break;
      case 'photo':
        $attachment = $this->request->getPost('attachment');
        if (!empty($photo = $this->upload('img1')) || empty($attachment)) {
          $question->attachment = json_encode([
            'type' => Questions::ATTACHMENT_PHOTO,
            'photo' => $photo
          ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
          $question->attachment = $attachment;
        }
        break;
      default:
        $question->attachment = null;
    }

    try {

      $manager = new TxManager();
      $transaction = $manager->get();
      $question->setTransaction($transaction);

      if ($question->save()) {

        $huntPoints = \HuntPoints::find('question_id = ' . $question->id . ' AND ' . (is_null($question->point_id) ? 'point_id IS NOT NULL' : ('point_id != ' . $question->point_id)));
        foreach ($huntPoints as $hp) {
          $hp->setTransaction($transaction);
          $hp->point_id = $question->point_id;
          if (!$hp->save()) {
            foreach ($hp->getMessages() as $message)
              $this->flash->error($message);
            $transaction->rollback();
          }
        }

        $transaction->commit();

      } else {
        $this->tag->setDefault('attachment', $question->attachment);
        $this->tag->setDefault('qattachment', $question->qattachment);

        foreach ($question->getMessages() as $message)
          $this->flash->error($message);

        $this->dispatcher->forward([
          'controller' => 'questions',
          'action' => 'edit',
          'params' => [$question->id]
        ]);

        return;
      }
    } catch (TxFailed $e) {
      $this->dispatcher->forward([
        'controller' => 'questions',
        'action' => 'edit',
        'params' => [$question->id]
      ]);

      return;
    }

    $tags = explode(',', $this->request->getPost('tags', 'trim'));
    $oldTags = array_map('array_pop', $this->db->fetchAll('SELECT tag_id FROM question_tags WHERE question_id=' . (int)$question->id));
    $toDelete = array_diff($oldTags, $tags);
    $toAdd = array_diff($tags, $oldTags);
    foreach ($toDelete as $tag) {
      if (!($tag > 0)) continue;
      $t = QuestionTags::findFirst('question_id=' . (int)$question->id . ' AND tag_id=' . (int)$tag);
      if (!$t)
        continue;
      if (!$t->delete())
        $this->flash->error('Failed to delete question tag');
    }
    foreach ($toAdd as $tag) {
      if (!($tag > 0)) continue;
      $t = new QuestionTags();
      $t->question_id = $question->id;
      $t->tag_id = $tag;
      if (!$t->save())
        $this->flash->error('Failed to save question tag');
    }

    $this->flash->success('Question was updated successfully');

    $this->response->redirect('questions');

  }

  /**
   * Deletes a question
   *
   * @param string $id
   */
  public function deleteAction($id)
  {
    if ($this->requireUser())
      return true;
    $question = Questions::findFirstByid($id);
    if (!$question) {
      $this->flash->error('Question was not found');

      $this->response->redirect('questions');

      return;
    }

    if ($question->delete()) {
      $this->flash->success('Question was deleted successfully');
    } else {
      foreach ($question->getMessages() as $message)
        $this->flash->error($message);
    }

    $this->response->redirect('questions');
  }

  public function getQuestionsByPointAction($id = 0)
  {
    if ($this->requireUser())
      throw new \Exception(403, 403);

    $results = [
      ['id' => '', 'text' => '']
    ];

    $questionTypes = QuestionTypes::find()->toArray();
    $questionTypes = array_combine(array_map(function($c){
      return $c['id'];
    }, $questionTypes), array_map(function($c){
      return [$c['name'], $c['score']];
    }, $questionTypes));

    $tags = implode(',', array_map(function($n){
      return (int)$n;
    }, explode(',', $this->request->getQuery('tags', 'trim'))));

    if (empty($tags)) {
      $questions = Questions::find($id > 0 ? 'point_id = ' . (int)$id : 'point_id IS NULL');
    } else {
      $questions = new \Phalcon\Mvc\Model\Query\Builder([
        'models'     => ['qt' => 'QuestionTags'],
        'columns' => 'q.id, q.type_id, q.score, q.question',
        'conditions' => 'qt.tag_id IN (' . $tags . ') AND q.point_id' . ($id > 0 ? '=' . (int)$id : ' IS NULL')
      ]);
      $questions->leftJoin('Questions', 'qt.question_id = q.id', 'q');
      ///$questions->groupBy('q.id');
      $questions = $questions->getQuery()->execute();
    }

    foreach ($questions as $question) {
      $results[] = [
        'id' => (int)$question->id,
        'text' => mb_strimwidth($questionTypes[$question->type_id][0] . ' (' . ($question->score ?? $questionTypes[$question->type_id][1]) . '): ' . $question->question , 0 , 159, '...')
      ];
    }

    return $this->jsonResponse([
      'success' => true,
      'results' => $results
    ]);
  }

  public function XLSXAction()
  {
    if ($this->requireUser())
      return true;

    $writer = new \XLSXWriter();
    $writer->writeSheetHeader('Questions', [
      'ID' => 'integer',
      'Point' => 'string',
      'Type' => 'string',
      'Score' => 'integer',
      'Question' => 'string',
      'Timeout' => 'string',
      'Hint' => 'string',
      'Fun Facts' => 'string',
      'Answers' => 'string',
      'Active Hunts' => 'string'
    ]);

    $questions = new \Phalcon\Mvc\Model\Query\Builder([
      'models'     => ['Questions'],
      'columns' => 'Questions.id, p.name as pointname, qt.name as type, Questions.score, Questions.question, Questions.hint, Questions.funfact, Questions.timeout, Questions.answers, GROUP_CONCAT(ah.name) as activehunts',
      'conditions' => 'Questions.point_id > 1 AND ah.id IS NOT NULL'
    ]);
    $questions->leftJoin('QuestionTypes', 'qt.id = Questions.type_id', 'qt');
    $questions->leftJoin('Points', 'p.id = Questions.point_id', 'p');
    $questions->leftJoin('HuntPoints', 'hp.point_id = p.id', 'hp');
    $questions->leftJoin('Hunts', 'ah.id = hp.hunt_id AND ah.approved = 1', 'ah');
    $questions->groupBy('Questions.id');
    $questions = $questions->getQuery()->execute();
    
    foreach ($questions as $question) {
      $writer->writeSheetRow('Questions', [
        (int)$question->id,
        $question->pointname,
        $question->type,
        is_null($question->score) ? 0 : (int)$question->score,
        $question->question,
        gmdate('H:i:s', $question->timeout),
        $question->hint,
        $question->funfact,
        str_replace("\n", '|', $question->answers),
        $question->activehunts
      ]);
    }

    header('Content-disposition: attachment; filename="questions.xlsx"');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Transfer-Encoding: binary');
    $writer->writeToStdOut();
    exit;
  }

}

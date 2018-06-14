<?php

namespace DataTables\Adapters;

use DataTables\ParamsParser;

abstract class AdapterInterface {

  protected $parser  = null;
  protected $columns = [];
  protected $clean_columns = [];
  protected $lentgh  = 30;

  public function __construct($length) {
    $this->length = $length;
  }

  abstract public function getResponse();

  public function setParser(ParamsParser $parser) {
    $this->parser = $parser;
  }

  public function setColumns(array $columns) {
    $this->clean_columns = $this->columns = [];
    foreach ($columns as $c) {
      $as = stristr($c, ' as ');
      if ($as === false) {
        $this->columns[] = $c;
        $pos = strpos($c, '.');
        $this->clean_columns[] = $pos === false ? $c : substr($c, $pos + 1);
      } else {
        $cc = stristr($c, ' as ', true);
        if ($cc && stristr($cc, 'SELECT ') === false) {
          $this->columns[] = $cc;
          $this->clean_columns[] = substr($as, 4);
        } else {
          $this->columns[] = '';
          $this->clean_columns[] = '';
        }
      }
    }
  }

  public function getColumns() {
    return $this->columns;
  }

  public function columnExists($column) {
    return in_array($column, $this->columns);
  }

  public function getParser() {
    return $this->parser;
  }

  public function formResponse($options) {
    $defaults = [
      'total'     => 0,
      'filtered'  => 0,
      'data'      => []
    ];
    $options += $defaults;

    $response = [];
    $response['draw'] = $this->parser->getDraw();
    $response['recordsTotal'] = $options['total'];
    $response['recordsFiltered'] = $options['filtered'];

    if (count($options['data'])) {
      foreach($options['data'] as $item) {
        if (isset($item['id'])) {
          $item['DT_RowId'] = $item['id'];
        }

        $response['data'][] = $item;
      }
    } else {
      $response['data'] = [];
    }

    return $response;
  }

  public function sanitaze($string) {
    return mb_substr($string, 0, $this->length);
  }

  public function bind($case, $closure) {
    switch($case) {
      case "global_search":
        $search = $this->parser->getSearchValue();
        if (!mb_strlen($search)) return;
        $search = $this->sanitaze($search);
        foreach($this->parser->getSearchableColumns() as $column) {
          if (!$this->columnExists($column)) {
            if (($x = array_search($column, $this->clean_columns)) !== false)
              $column = $this->columns[$x];
            else continue;
          }
          $closure($column, $search);
        }
        break;
      case "column_search":
        $columnSearch = $this->parser->getColumnsSearch();
        if (!$columnSearch) return;

        foreach($columnSearch as $key => $column) {
          if (!$this->columnExists($column['data'])) {
            if (($x = array_search($column['data'], $this->clean_columns)) !== false)
              $column['data'] = $this->columns[$x];
            else continue;
          }
          $closure($column['data'], $this->sanitaze($column['search']['value']));
        }
        break;
      case "order":
        $order = $this->parser->getOrder();
        if (!$order) return;

        $orderArray = [];

        foreach($order as $orderBy) {
          if (!isset($orderBy['dir']) || !isset($orderBy['column'])) continue;
          $orderDir = $orderBy['dir'];

          $column = $this->parser->getColumnById($orderBy['column']);
          if (is_null($column)) continue;
          if (!$this->columnExists($column)) {
            if (($x = array_search($column, $this->clean_columns)) !== false)
              $column = $this->columns[$x];
            else continue;
          }

          $orderArray[] = "{$column} {$orderDir}";
        }

        $closure($orderArray);
        break;
      default:
        throw new \Exception('Unknown bind type');
    }

  }

}

<?php

namespace Cygnus\DrushExport;

/**
 * Provides an abstraction of MongoDB interactions
 */
final class DBAL {

  private $instance;
  private $isMongoDB = false;

  public function __construct($dsn) {
    if (class_exists('MongoClient')) {
      $this->instance = new \MongoClient($dsn);
    } else {
      $this->isMongoDB = true;
      $this->instance = new \MongoDB\Client($dsn);
    }
  }

  public function getDb($db) {
    return $this->isMongoDB
      ? $this->instance->{$db}
      : $this->instance->selectDb($db);
  }

  public function getCollection($db, $coll) {
    return $this->isMongoDB
      ? $this->instance->{$db}->{$coll}
      : $this->instance->selectDb($db)->selectCollection($coll);
  }

  public function insert($db, $coll, $payload) {
    $coll = $this->getCollection($db, $coll);
    return $this->isMongoDB
      ? $coll->insertOne($payload)
      : $coll->insert($payload);
  }

  public function batchInsert($db, $coll, $documents) {
    $coll = $this->getCollection($db, $coll);
    return $this->isMongoDB
      ? $coll->insertMany($documents)
      : $coll->insert($documents);
  }

  public function batchUpsert($db, $coll, $ops) {
    $collection = $this->getCollection($db, $coll);
    if (!$this->isMongoDB) throw new \InvalidArgumentException('Batch upsert not supported.');
    $bulkOps = array_map(function ($op) {
      return [
        'updateOne' => [
          $op['filter'],
          $op['update'],
          ['upsert'  => true ],
        ]
      ];
    }, $ops);
    return $collection->bulkWrite($bulkOps);
  }

}
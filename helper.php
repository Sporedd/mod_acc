<?php
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Http\HttpFactory;

class ModACCHelper {

  protected Joomla\CMS\Cache\CacheController $cache;

  protected string $cacheId;

  private Joomla\Registry\Registry $params;

  private array $carList;

  protected function getCars(): array {
    $db = JFactory::getDbo();
    $query = $db->getQuery(TRUE);
    $carList = [];

    try {
      // Select the required fields from the table.
      $query->select('DISTINCT a.*');
      $query->from('`#__acc_cars` AS a');
      $db->setQuery($query);
      $results = $db->loadAssocList();

      foreach ($results as $car) {
        $carList[$car['acc_id']] = $car;
      }
    } catch (Exception $e) {

    }

    return $carList;
  }

  public function __construct($params) {
    $this->cache = JFactory::getCache('mod_acc', '');
    $this->params = $params;
    $this->cacheId = 'mod_acc_' . md5($this->params->get('server_path', 'randomstring'));
    $this->carList = $this->getCars();
  }

  public function getData(): array {
    if ($this->cache->contains($this->cacheId)) {
      return $this->cache->get($this->cacheId);
    }
    $http = HttpFactory::getHttp();
    if ($this->params->get('server')) {
      $response = $http->post($this->params->get('server'), [
        'AUTH_KEY' => $this->params->get('auth_key', NULL),
        'RESULTS_SERVER_PATH' => $this->params->get('server_path', NULL),
      ]);
    }
    else {
      return [];
    }
    if ($response->code !== 200) {
      return [];
    }
    else {
      $data['response'] = json_decode($response->body);
      $data['timestamp'] = date('d-m-y H:i:s');
      $this->cache->store($data, $this->cacheId);

      return $data;
    }
  }

  protected static function sortByTime($a, $b): int {
    return $a->timing->bestLap - $b->timing->bestLap;
  }

  public function milisecondsToTimeStap($time, $minutes = TRUE): string {
    $string = '';
    if ($minutes) {
      $string .= floor($time / 60000) . ':';
    }
    $string .= str_pad(floor(($time % 60000) / 1000), 2, '0', STR_PAD_LEFT) . ':' . str_pad(floor($time % 1000), 3, '0', STR_PAD_LEFT);;

    return $string;
  }

  public function init(): array {
    $data = $this->getData();
    $results = [];

    if (isset($data['response']) && $data['response']) {
      $bestResults = [];
      $lapCounts = [];
      foreach ($data['response'] as $key => $result) {
        if (isset($result->sessionResult) && $lines = $result->sessionResult->leaderBoardLines) {
          foreach ($lines as $line) {
            if ($line->timing->bestLap > 200000) {
              continue;
            }
            if (isset($bestResults[$line->currentDriver->playerId])) {
              if ($line->timing->bestLap < $bestResults[$line->currentDriver->playerId]->timing->bestLap) {
                $bestResults[$line->currentDriver->playerId] = $line;
              }

            }
            else {
              $bestResults[$line->currentDriver->playerId] = $line;
            }
            if (isset($lapCounts[$line->currentDriver->playerId])) {
              $lapCounts[$line->currentDriver->playerId] += $line->timing->lapCount;
            }
            else {
              $lapCounts[$line->currentDriver->playerId] = $line->timing->lapCount;
            }
          }
        }
      }

      usort($bestResults, 'ModACCHelper::sortByTime');

      $gap = [];
      foreach ($bestResults as $currentKey => $bestResult) {
        if ($currentKey > 0) {
          $gap[] = $bestResult->timing->bestLap - $bestResults[$currentKey - 1]->timing->bestLap;
        }

        if (isset($this->carList[$bestResult->car->carModel])) {
          $bestResult->car = $this->carList[$bestResult->car->carModel];
        }
        else {
          $bestResult->car = 'Id: ' . $bestResult->car->carModel;
        }

      }
      if (count($gap)) {
        $results[$key]['avgGap'] = array_sum($gap) / count($gap);
      }
      $results[$key]['serverName'] = $data['response'][0]->serverName;
      $results[$key]['next_update'] = date("i:s", strtotime($data['timestamp']) - strtotime('+30 minutes 1 second'));
      $results[$key]['results'] = $bestResults;
      $results[$key]['lapCounts'] = $lapCounts;
      $results[$key]['totalLapCount'] = array_sum($lapCounts);
    }


    return $results;
  }

}
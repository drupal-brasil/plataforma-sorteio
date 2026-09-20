<?php

namespace Drupal\db_lottery;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

class LotteryStorage extends SqlContentEntityStorage {
    public function loadLatestActive() {
        $query = $this->getQuery()
            ->condition('status', 1)
            ->sort('created', 'DESC')
            ->range(0, 1)
            ->accessCheck(FALSE);

        $ids = $query->execute();

        return $ids ? $this->load(reset($ids)) : NULL;
    }

}
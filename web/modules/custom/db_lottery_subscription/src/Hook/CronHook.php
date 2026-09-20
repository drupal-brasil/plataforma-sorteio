<?php

namespace Drupal\db_lottery_subscription\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;

class CronHook {

	protected EntityTypeManagerInterface $entityTypeManager;
	protected TimeInterface $time;

	public function __construct(EntityTypeManagerInterface $entityTypeManager, TimeInterface $time) {
		$this->entityTypeManager = $entityTypeManager;
		$this->time = $time;
	}

	/**
	 * Implements hook_cron().
	 */
	#[Hook('cron')]
	public function cron(): void {
		$lottery = $this->entityTypeManager->getStorage('lottery');

		$last_lottery_activated = $lottery->loadLatestActive();

		$end_date_value = $last_lottery_activated?->getEndDate()->getTimestamp();

		if (! $end_date_value) {
			return;
		}

		$now = $this->time->getRequestTime();

		if ($now <= $end_date_value) {
			return;
		}

		$this->deactivateAllPrizes();
		$this->deactivateAllLotteries();

		\Drupal::logger('db_lottery_subscription')->notice('Cron executada com sucesso!');

	}

	/**
	 * Deactivate all lotteries.
	 */
	private function deactivateAllLotteries(): void {
		$storage = $this->entityTypeManager->getStorage('lottery');

		$ids = $storage->getQuery()
			->accessCheck(FALSE)
			->execute();
		if (!$ids) return;

		foreach ($storage->loadMultiple($ids) as $ent) {
			$ent->set('status', 0);
			$ent->save();
		}
	}

	/**
	 * Deactivate all prizes.
	 */
	private function deactivateAllPrizes(): void {
		$storage = $this->entityTypeManager->getStorage('prize');

		$ids = $storage->getQuery()
			->accessCheck(FALSE)
			->execute();
		if (!$ids) return;

		foreach ($storage->loadMultiple($ids) as $ent) {
			$ent->set('status', 0);
			$ent->save();
		}
	}

}
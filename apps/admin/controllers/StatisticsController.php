<?php

namespace Play\Admin\Controllers;

use \Exception;

class StatisticsController extends \ControllerBase
{
	public function indexAction()
	{
		if ($this->requireUser())
			return true;

		$this->assets->collection('script')
					->addJs('/template/js/plugins/chartJs/Chart.min.js')
					->addJs('/template/js/plugins/daterangepicker/daterangepicker.js');
					
		$this->assets->collection('style')
					->addCss('/template/css/plugins/daterangepicker/daterangepicker-bs3.css');
					
		// Get basic statistics about the system
		$stats = [
			'users' => $this->getStats('Users'),
			'clients' => $this->getStats('Clients'),
			'orders' => $this->getStats('Orders'),
			'hunts' => $this->getStats('Hunts'),
			'points' => $this->getStats('Points'),
			'questions' => $this->getStats('Questions')
		];
		
		$this->view->stats = $stats;
		
		// Get monthly order stats for the chart
		$this->view->monthlyStats = $this->getMonthlyStats();
	}
	
	/**
	 * Get basic statistics for a model
	 */
	private function getStats($model)
	{
		// Get total count
		$count = $this->modelsManager->createBuilder()
			->columns('COUNT(*) as count')
			->from($model)
			->getQuery()
			->getSingleResult();
			
		// Get count of active records (if active field exists)
		$activeCount = 0;
		try {
			$activeCount = $this->modelsManager->createBuilder()
				->columns('COUNT(*) as count')
				->from($model)
				->where('active = 1')
				->getQuery()
				->getSingleResult();
		} catch (\Exception $e) {
			// Active field might not exist, ignore error
		}
		
		// Get count of recently added records (last 30 days)
		$recentCount = 0;
		try {
			$recentCount = $this->modelsManager->createBuilder()
				->columns('COUNT(*) as count')
				->from($model)
				->where('created >= :date:', ['date' => date('Y-m-d', strtotime('-30 days'))])
				->getQuery()
				->getSingleResult();
		} catch (\Exception $e) {
			// Created field might not exist, ignore error
		}
		
		return [
			'total' => $count->count,
			'active' => $activeCount ? $activeCount->count : null,
			'recent' => $recentCount ? $recentCount->count : null
		];
	}
	
	/**
	 * Get monthly statistics for orders/hunts for the past 12 months
	 */
	private function getMonthlyStats()
	{
		$months = [];
		$ordersData = [];
		$huntsData = [];
		
		// Get last 12 months
		for ($i = 11; $i >= 0; $i--) {
			$date = strtotime("-$i months");
			$monthYear = date('M Y', $date);
			$monthStart = date('Y-m-01', $date);
			$monthEnd = date('Y-m-t', $date);
			
			$months[] = $monthYear;
			
			// Get orders count for this month
			$ordersCount = $this->modelsManager->createBuilder()
				->columns('COUNT(*) as count')
				->from('Orders')
				->where('created >= :start: AND created <= :end:', [
					'start' => $monthStart,
					'end' => $monthEnd . ' 23:59:59'
				])
				->getQuery()
				->getSingleResult();
				
			$ordersData[] = $ordersCount->count;
			
			// Get hunts count for this month
			$huntsCount = $this->modelsManager->createBuilder()
				->columns('COUNT(*) as count')
				->from('OrderHunts')
				->where('start >= :start: AND start <= :end:', [
					'start' => $monthStart,
					'end' => $monthEnd . ' 23:59:59'
				])
				->getQuery()
				->getSingleResult();
				
			$huntsData[] = $huntsCount->count;
		}
		
		return [
			'months' => $months,
			'orders' => $ordersData,
			'hunts' => $huntsData
		];
	}
} 
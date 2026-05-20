<?php
declare(strict_types=1);

namespace FORM4\Form4SyslogReport\Service;

/*
 * Copyright notice
 *
 * (c) 2016-2023 form4 GmbH & Co. KG <typo3@form4.de>
 * All rights reserved
 *
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Symfony\Component\Console\Input\InputInterface;
use TYPO3\CMS\Belog\Domain\Model\Constraint;
use TYPO3\CMS\Belog\Domain\Model\LogEntry;
use TYPO3\CMS\Belog\Domain\Repository\LogEntryRepository;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewInterface;

class LogService
{
    private int $startTimeStamp;

    private int $endTimeStamp;

    /**
     * @var LogEntryRepository
     */
    protected LogEntryRepository $logEntryRepository;

    public function __construct(LogEntryRepository $logEntryRepository)
    {
        $this->logEntryRepository = $logEntryRepository;
    }

    /**
     * Validates required variables.
     *
     * @throws Exception If $period is empty
     * @throws Exception If $receivers is empty
     * @throws Exception If the calculated starttime is in the future
     * @throws Exception If at least one receiver email address is invalid
     */
    public function validateVariables(array $receivers, string $period): void
    {
        if (empty($period)) {
            throw new Exception('Please enter a period with <a href="http://php.net/manual/en/datetime.modify.php" target="_blank">"DateTime::modify"</a> format for the report.', 1460714278);
        }
        if (empty($receivers)) {
            throw new Exception('You need to enter at least one email address as receiver of the report.', 1460714279);
        }

        // use the current time as end
        $this->endTimeStamp = time();
        // modify the current time with $period to get the starttime
        $start = new \DateTime();
        $start->setTimestamp($this->endTimeStamp);
        @$start->modify($period);
        $this->startTimeStamp = $start->getTimestamp();

        // the starttime must be smaller than the endtime
        if ($this->startTimeStamp >= $this->endTimeStamp) {
            throw new Exception('Please enter valid period with <a href="http://php.net/manual/en/datetime.modify.php" target="_blank">"DateTime::modify"</a> format for a past date.', 1460714276);
        }

        // all email addresses must be valid
        foreach ($receivers as $receiver) {
            if (!GeneralUtility::validEmail($receiver)) {
                throw new Exception('The email address "' . $receiver . '" is invalid.', 1460714277);
            }
        }
    }

    /**
     * @param InputInterface $input
     * @return bool
     */
    public function hasAnyOptionActivated(InputInterface $input): bool
    {
        foreach ($input->getOptions() as $optionValue) {
            if ($optionValue) {
                return true;
            }
        }
        return false;
    }

    public function clearCachePagesLogs(iterable $logEntries, ViewInterface $view): void
    {
        $logEntriesPages = array();
        foreach ($logEntries as $logEntry) {
            $logData = $logEntry->getLogData();
            if (in_array('pages', $logData)) {
                $logEntriesPages[] = $logEntry;
            }
        }
        $view->assign('logEntriesPages', $logEntriesPages);
    }

    /**
     * @param iterable $logEntries
     * @param ViewInterface $view
     *
     * @return void
     */
    public function clearCacheAllLogs(iterable $logEntries, ViewInterface $view): void
    {
        $logEntriesAll = [];
        foreach ($logEntries as $logEntry) {
            $logData = $logEntry->getLogData();
            if (in_array('all', $logData)) {
                $logEntriesAll[] = $logEntry;
            }
        }
        $view->assign('logEntriesAll', $logEntriesAll);
    }

    /**
     * @param string $clearcachepage
     * @param iterable $logEntries
     * @param ViewInterface $view
     *
     * @return void
     */
    public function clearCachePageLogs(string $clearcachepage, iterable $logEntries, ViewInterface $view): void
    {
        $clearcachepageArray = array_map('intval', GeneralUtility::trimExplode(',', $clearcachepage, true));
        $logEntriesPage = [];
        foreach ($logEntries as $logEntry) {
            $logData = $logEntry->getLogData();

            foreach ($clearcachepageArray as $clearcachepageUid) {
                if (in_array($clearcachepageUid, $logData) && str_contains($logEntry->getDetails(), 'cleared the cache')) {
                    $logEntriesPage[$clearcachepageUid][] = $logEntry;
                }
            }
        }
        $view->assign('logEntriesPage', $logEntriesPage);
    }

    /**
     * @param string $errorFilterRegex
     * @param ViewInterface $view
     *
     * @return void
     */
    public function errorLogs(string $errorFilterRegex, ViewInterface $view): void
    {
        // get all Cache entries
        $constraint = $this->buildConstraint();
        $constraint->setChannel('php');
        // get log entries grouped
        $logEntries = $this->logEntryRepository->findByConstraint($constraint);
        $logEntriesArray = is_array($logEntries) ? $logEntries : $logEntries->toArray();

        $logEntries = array_filter($logEntriesArray, function ($entry) {
            /* @var $entry \TYPO3\CMS\Belog\Domain\Model\LogEntry */
            return $entry->getError() == 2;
        });

        if (!empty($errorFilterRegex)) {
            $logEntries = array_filter($logEntries, function ($entry) use ($errorFilterRegex) {
                /* @var $entry \TYPO3\CMS\Belog\Domain\Model\LogEntry */
                return preg_match_all($errorFilterRegex, $entry->getDetails());
            });
        }
        $view->assign('logEntriesError', $this->groupLogEntriesByPageAndDay($logEntries));
    }

    /**
     * @param string $errorFilterRegex
     * @param ViewInterface $view
     *
     * @return void
     */
    public function warningLogs(string $errorFilterRegex, ViewInterface $view): void
    {
        // get all Cache entries
        $constraint = $this->buildConstraint();
        $constraint->setChannel('php');
        // get log entries grouped
        $logEntries = $this->logEntryRepository->findByConstraint($constraint);
        $logEntriesArray = is_array($logEntries) ? $logEntries : $logEntries->toArray();

        $logEntries = array_filter($logEntriesArray, function ($entry) {
            /* @var $entry \TYPO3\CMS\Belog\Domain\Model\LogEntry */
            return $entry->getError() == 1;
        });

        if (!empty($errorFilterRegex)) {
            $logEntries = array_filter($logEntries, function ($entry) use ($errorFilterRegex) {
                /* @var $entry \TYPO3\CMS\Belog\Domain\Model\LogEntry */
                return preg_match_all($errorFilterRegex, $entry->getDetails());
            });
        }

        $logEntriesGrouped = $this->groupLogEntriesByPageAndDay($logEntries);
        $view->assign('logEntriesWarning', $logEntriesGrouped);
    }

    /**
     * Creates a sorted array for day and page view from the query result of the sys log repository.
     *
     * This method was copied from \TYPO3\CMS\Belog\Controller\AbstractController.
     *
     * If group by page is FALSE, pid is always -1 (will render a flat list),
     * otherwise the output is split by pages.
     * '12345' is a sub array to split entries by day, number is first second of day
     * [pid][dayTimestamp][items]
     *
     * @param LogEntry[] $logEntries
     * @param boolean $groupByPage Whether log entries should be grouped by page
     * @return array
     */
    protected function groupLogEntriesByPageAndDay(array $logEntries, bool $groupByPage = false): array
    {
        $targetStructure = [];
        foreach ($logEntries as $entry) {
            // Create page split list or flat list
            if ($groupByPage) {
                $pid = $entry->getEventPid();
            } else {
                $pid = -1;
            }

            // Create array if it is not defined yet
            if (!isset($targetStructure[$pid])) {
                $targetStructure[$pid] = [];
            }
            // Get day timestamp of log entry and create sub array if needed
            $tStamp = $entry->getTstamp();
            $timestampDay = $tStamp->setTime(0, 0, 0)->getTimestamp();
            if (!isset($targetStructure[$pid][$timestampDay])) {
                $targetStructure[$pid][$timestampDay] = [];
            }
            // Add row
            $targetStructure[$pid][$timestampDay][] = $entry;
        }
        ksort($targetStructure);
        return $targetStructure;
    }

    /**
     * Builds contraint for the LogEntryRepository.
     *
     * @return Constraint
     */
    public function buildConstraint(): Constraint
    {
        /** @var Constraint $constraint */
        $constraint = GeneralUtility::makeInstance(Constraint::class);
        $constraint->setNumber(1000000);
        $constraint->setStartTimestamp($this->startTimeStamp);
        $constraint->setEndTimestamp($this->endTimeStamp);
        return $constraint;
    }

    public function getStartTimeStamp(): int
    {
        return $this->startTimeStamp;
    }

    public function getEndTimeStamp(): int
    {
        return $this->endTimeStamp;
    }

}

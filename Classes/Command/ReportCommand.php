<?php

namespace FORM4\Form4SyslogReport\Command;

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

use FORM4\Form4SyslogReport\Service\LogService;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Belog\Domain\Repository\LogEntryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Exception;

/**
 * Command Controller for reports of sys_log entries.
 *
 * @author Kerstin Gerull <kerstin.gerull@form4.de>
 */
class ReportCommand extends Command
{
    /**
     * @var FlashMessageService
     */
    protected FlashMessageService $flashMessageService;

    /**
     * @var LogEntryRepository
     */
    protected LogEntryRepository $logEntryRepository;

    /**
     * @var array
     */
    protected array $extensionConfiguration;

    /**
     * @var array
     */
    protected array $customProperties = [];

    /**
     * @var LogService
     */
    protected LogService $logService;


    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this->setDescription('Run content importer. Without arguments all available wizards will be run.')
            ->addArgument(
                'period',
                InputArgument::OPTIONAL,
                'Period with "DateTime::modify" format (https://php.net/manual/en/datetime.modify.php)'
            )
            ->addArgument(
                'receiver',
                InputArgument::OPTIONAL,
                'Receiver of the sys_log report. (comma separated list of email addresses)'
            )
            ->addArgument(
                'subject',
                InputArgument::OPTIONAL,
                'Subject of the email. (e.g. "SysLog report from %s to %s" markers will be substituted with "startdate" and "enddate" of the report.)'
            )
            ->addArgument(
                'errorFilterRegex',
                InputArgument::OPTIONAL,
                'Regular expression for filtering error messages'
            )
            ->addArgument(
                'clearcachepage',
                InputArgument::OPTIONAL,
                'Report "cacheCmd=xxx" Delete cache for special pages (comma separated list of page uids)'
            )
            ->addOption(
                'errors',
                'er',
                InputOption::VALUE_NONE,
                'Report all errors'
            )
            ->addOption(
                'warnings',
                'wa',
                InputOption::VALUE_NONE,
                'Report all warnings'
            )
            ->addOption(
                'clearcachepages',
                'cps',
                InputOption::VALUE_NONE,
                'Report "cacheCmd=pages" "Flush frontend caches"'
            )
            ->addOption(
                'clearcacheall',
                'ca',
                InputOption::VALUE_NONE,
                'Report "cacheCmd=all" "Flush general caches"'
            );


        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['syslogReport']['modifyConfiguration'] ?? [] as $classData) {
            $hookObject = GeneralUtility::makeInstance($classData);
            if (!$hookObject instanceof ModifyConfigurationHookInterface) {
                throw new \UnexpectedValueException('$hookObject must implement interface ' . ModifyConfigurationHookInterface::class, 1646128613);
            }
            /** @var ModifyConfigurationHookInterface $hookObject */
            $hookObject->modifyConfiguration($this);
        }
    }

    public function __construct(FlashMessageService $flashMessageService, LogEntryRepository $logEntryRepository, LogService $logService)
    {
        $this->flashMessageService = $flashMessageService;
        $this->logEntryRepository = $logEntryRepository;
        $this->logService = $logService;
        parent::__construct('form4_syslog_report:report');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $subject
     *
     * @return int|void
     * @throws Exception
     */
    public function execute(
        InputInterface  $input,
        OutputInterface $output,
        string          $subject = 'SysLog report from %s to %s',
    )
    {
        $subject = $input->getArgument('subject') ? $input->getArgument('subject') : $subject;
        $clearcachepage = $input->getArgument('clearcachepage');
        $this->extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            ExtensionConfiguration::class
        )->get('form4_syslog_report');

        if (!$this->logService->hasAnyOptionActivated($input) && empty($clearcachepage)) {
            return 1;
        }
        $receivers = GeneralUtility::trimExplode(',', $input->getArgument('receiver'), true);

        // validate variables, throws exceptions when failing
        $this->logService->validateVariables($receivers, $input->getArgument('period'));

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(
            Environment::getPublicPath() .
            '/typo3conf/ext/form4_syslog_report/Resources/Private/Templates/Report/Mail.html');
        $view->setPartialRootPaths(
            [Environment::getPublicPath() . '/typo3conf/ext/form4_syslog_report/Resources/Private/Partials/']
        );

        // get all Cache entries
        $constraint = $this->logService->buildConstraint();

        // get log entries
        $logEntries = $this->logEntryRepository->findByConstraint($constraint);


        // clear cache pages
        if ($input->getOption('clearcachepages')) {
            $this->logService->clearCachePagesLogs($logEntries, $view);
        }

        // clear cache all
        if ($input->getOption('clearcacheall')) {
            $this->logService->clearCacheAllLogs($logEntries, $view);
        }

        // clear cache of special pages
        if (!empty($clearcachepage)) {
            $this->logService->clearCachePageLogs($clearcachepage, $logEntries, $view);
        }

        // report all errors
        if ($input->getOption('errors')) {
            $this->logService->errorLogs($input->getArgument('errorFilterRegex'), $view);
        }
        // report all warnings
        if ($input->getOption('warnings')) {
            $this->logService->warningLogs($input->getArgument('errorFilterRegex'), $view);
        }

        $customEntries = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['syslogReport']['fetchLogEntries'] ?? [] as $classData) {
            $hookObject = GeneralUtility::makeInstance($classData);
            if (!$hookObject instanceof FetchLogEntriesHookInterface) {
                throw new \UnexpectedValueException('$hookObject must implement interface ' . FetchLogEntriesHookInterface::class, 1646128613);
            }
            /** @var FetchLogEntriesHookInterface $hookObject */
            $customEntries[] = $hookObject->fetchLogEntries($input, $this);
        }

        if (count($customEntries) > 0) {
            $view->assign('customEntries', $customEntries);
        }

        $view->assignMultiple([
            'title' => sprintf($subject, date('d.m.Y H:i:s', $this->logService->getStartTimeStamp()), date('d.m.Y H:i:s', $this->logService->getEndTimeStamp())),
            'start' => $this->logService->getStartTimeStamp(),
            'end' => $this->logService->getEndTimeStamp(),
        ]);

        $content = $view->render();

        // send mail to all receivers
        foreach ($receivers as $receiver) {
            $success = $this->sendMail($receiver, $content, $subject);
            if (!$success) {
                return 1;
            }
        }
        return 0;
    }


    protected function sendMail(string $receiverEmail, string $content, string $subject): bool
    {
        // process the subject: substitute start and end date for placeholders and prepend the sitename
        $subject = sprintf($subject, date('d.m.Y H:i:s', $this->startTimeStamp), date('d.m.Y H:i:s', $this->endTimeStamp));
        $subject = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . ': ' . $subject;

        $senderName = $this->extensionConfiguration['senderName'];
        $senderEmail = $this->extensionConfiguration['senderEmailAddress'];

        // send the mail if receiver and subject are given
        if ($receiverEmail && $subject) {
            /** @var MailMessage $mail */
            $mail = GeneralUtility::makeInstance(MailMessage::class);
            $mail->subject($subject);
            $mail->from(GeneralUtility::makeInstance(\Symfony\Component\Mime\Address::class, $senderEmail, $senderName));
            $mail->to(GeneralUtility::makeInstance(\Symfony\Component\Mime\Address::class, $receiverEmail));
            $mail->html($content);
            $mail->send();
            $success = $mail->isSent();
        } else {
            $success = false;
        }

        return $success;
    }

    /**
     * @param string $propertyName
     * @return void
     */
    public function addCustomProperty(string $propertyName): void
    {
        $this->customProperties[] = $propertyName;
    }
}

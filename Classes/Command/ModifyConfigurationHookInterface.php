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

interface ModifyConfigurationHookInterface
{

    /**
     * @param ReportCommand $reportCommand
     * @return void
     */
    public function modifyConfiguration(\FORM4\Form4SyslogReport\Command\ReportCommand $reportCommand): void;
}

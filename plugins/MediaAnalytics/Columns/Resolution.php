<?php
/**
 * Copyright (C) InnoCraft Ltd - All rights reserved.
 *
 * NOTICE:  All information contained herein is, and remains the property of InnoCraft Ltd.
 * The intellectual and technical concepts contained herein are protected by trade secret or copyright law.
 * Redistribution of this information or reproduction of this material is strictly forbidden
 * unless prior written permission is obtained from InnoCraft Ltd.
 *
 * You shall use this code only in accordance with the license agreement obtained from InnoCraft Ltd.
 *
 * @link https://www.innocraft.com/
 * @license For license details see https://www.innocraft.com/license
 */
namespace Piwik\Plugins\MediaAnalytics\Columns;

use Piwik\Piwik;

class Resolution extends MediaDimension
{

    protected $nameSingular = 'MediaAnalytics_Resolution';
    protected $namePlural = 'MediaAnalytics_Resolutions';
    protected $columnName = 'resolution';

    public function __construct()
    {
        if (defined('self::TYPE_TEXT')) {
            // only defined in Matomo 3.0.5 or 3.1
            $this->type = self::TYPE_TEXT;
        }
    }

    public function getName()
    {
        return Piwik::translate($this->nameSingular);
    }
}
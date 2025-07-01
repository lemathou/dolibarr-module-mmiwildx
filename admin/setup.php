<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2022-2025 MOULIN Mathieu <mathieu@iprospective.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    mmiwildx/admin/setup.php
 * \ingroup mmiwildx
 * \brief   MMIWildx setup page.
 */

// Load Dolibarr environment
require_once '../env.inc.php';
require_once '../main_load.inc.php';

// Parameters
$arrayofparameters = array(
	'MMI_WILDX_HOST'=>array('type'=>'string','enabled'=>1),
	'MMI_WILDX_APP_ID'=>array('type'=>'string', 'enabled'=>1),
	'MMI_WILDX_APP_NAME'=>array('type'=>'string', 'enabled'=>1),
	'MMI_WILDX_SECRET_KEY'=>array('type'=>'securekey', 'enabled'=>1),
);

require_once('../../mmicommon/admin/mmisetup_1.inc.php');

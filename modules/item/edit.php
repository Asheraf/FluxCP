<?php
if (!defined('FLUX_ROOT')) exit;

require_once 'Flux/Config.php';
require_once 'Flux/TemporaryTable.php';

$tableName  = "{$server->charMapDatabase}.items";
$fromTables = array("{$server->charMapDatabase}.item_db", "{$server->charMapDatabase}.item_db2");
$tempTable  = new Flux_TemporaryTable($server->connection, $tableName, $fromTables);

$title = 'Modify Item';

$itemID = $params->get('id');

if (!$itemID) {
	$this->deny();
}

$col  = "id, view, type, name_english, name_japanese, slots, price_buy, price_sell, weight/10 AS weight, attack, ";
$col .= "defence, range, weapon_level, equip_level, refineable, equip_locations, equip_upper, ";
$col .= "equip_jobs, equip_genders, script, equip_script, unequip_script, origin_table";
$sql  = "SELECT $col FROM $tableName WHERE id = ? LIMIT 1";
$sth  = $server->connection->getStatement($sql);
$sth->execute(array($itemID));

$item = $sth->fetch();

// Check if item exists, first.
if ($item) {
	$isCustom      = preg_match('/item_db2$/', $item->origin_table) ? true : false;
	
	$viewID        = $params->get('view')            ? $params->get('view')            : $item->view;
	$type          = $params->get('type')            ? $params->get('type')            : $item->type;
	$identifier    = $params->get('name_english')    ? $params->get('name_english')    : $item->name_english;
	$itemName      = $params->get('name_japanese')   ? $params->get('name_japanese')   : $item->name_japanese;
	$slots         = $params->get('slots')           ? $params->get('slots')           : $item->slots;
	$npcBuy        = $params->get('npc_buy')         ? $params->get('npc_buy')         : $item->price_buy;
	$npcSell       = $params->get('npc_sell')        ? $params->get('npc_sell')        : $item->price_sell;
	$weight        = $params->get('weight')          ? $params->get('weight')          : $item->weight;
	$attack        = $params->get('attack')          ? $params->get('attack')          : $item->attack;
	$defense       = $params->get('defense')         ? $params->get('defense')         : $item->defence;
	$range         = $params->get('range')           ? $params->get('range')           : $item->range;
	$weaponLevel   = $params->get('weapon_level')    ? $params->get('weapon_level')    : $item->weapon_level;
	$equipLevel    = $params->get('equip_level')     ? $params->get('equip_level')     : $item->equip_level;
	$refineable    = $params->get('refineable')      ? $params->get('refineable')      : $item->refineable;
	
	if ($item->equip_locations) {
		$item->equip_locations = Flux::equipLocationsToArray($item->equip_locations);
	}
	if ($item->equip_upper) {
		$item->equip_upper = Flux::equipUpperToArray($item->equip_upper);
	}
	if ($item->equip_jobs) {
		$item->equip_jobs = Flux::equipJobsToArray($item->equip_jobs);
	}
	
	$equipLocs     = $params->get('equip_locations') ? $params->get('equip_locations') : $item->equip_locations;
	$equipUpper    = $params->get('equip_upper')     ? $params->get('equip_upper')     : $item->equip_upper;
	$equipJobs     = $params->get('equip_jobs')      ? $params->get('equip_jobs')      : $item->equip_jobs;
	
	$equipMale     = ($item->equip_genders == 2 || $item->equip_genders == 1) ? true : false;
	$equipFemale   = ($item->equip_genders == 2 || $item->equip_genders == 0) ? true : false;
	
	$script        = $params->get('script') ? $params->get('script') : $item->script;
	$equipScript   = $params->get('equip_script') ? $params->get('equip_script') : $item->equip_script;
	$unequipScript = $params->get('unequip_script') ? $params->get('unequip_script') : $item->unequip_script;

	// Equip locations.
	if ($equipLocs instanceOf Flux_Config) {
		$equipLocs = $equipLocs->toArray();
	}

	// Equip upper.
	if ($equipUpper instanceOf Flux_Config) {
		$equipUpper = $equipUpper->toArray();
	}

	// Equip jobs.
	if ($equipJobs instanceOf Flux_Config) {
		$equipJobs = $equipJobs->toArray();
	}
	
	if (!is_array($equipLocs)) {
		$equipLocs = array();
	}
	if (!is_array($equipUpper)) {
		$equipUpper = array();
	}
	if (!is_array($equipJobs)) {
		$equipJobs = array();
	}

	if (count($_POST) && $params->get('edititem')) {
		// Sanitize to NULL: viewid, slots, npcbuy, npcsell, weight, attack, defense, range, weaponlevel, equiplevel
		$nullables = array(
			'viewID', 'slots', 'npcBuy', 'npcSell', 'weight', 'attack', 'defense',
			'range', 'weaponLevel', 'equipLevel', 'script', 'equipScript', 'unequipScript'
		);
		foreach ($nullables as $nullable) {
			if (trim($$nullable) == '') {
				$$nullable = null;
			}
		}

		// Weight is defaulted to an zero value.
		if (is_null($weight)) {
			$weight = 0;
		}

		// Refineable should be 1 or 0 if it's not null.
		if (!is_null($refineable)) {
			$refineable = intval((bool)$refineable);
		}

		if (!$itemID) {
			$errorMessage = 'You must specify an item ID.';
		}
		elseif (!ctype_digit($itemID)) {
			$errorMessage = 'Item ID must be a number.';
		}
		elseif (!is_null($viewID) && !ctype_digit($viewID)) {
			$errorMessage = 'View ID must be a number.';
		}
		elseif (!$identifier) {
			$errorMessage = 'You must specify an identifer.';
		}
		elseif (!$itemName) {
			$errorMessage = 'You must specify an item name.';
		}
		elseif (!is_null($slots) && !ctype_digit($slots)) {
			$errorMessage = 'Slots must be a number.';
		}
		elseif (!is_null($npcBuy) && !ctype_digit($npcBuy)) {
			$errorMessage = 'NPC buying price must be a number.';
		}
		elseif (!is_null($npcSell) && !ctype_digit($npcSell)) {
			$errorMessage = 'NPC selling price must be a number.';
		}
		elseif (!is_null($weight) && !ctype_digit($weight)) {
			$errorMessage = 'Weight must be a number.';
		}
		elseif (!is_null($attack) && !ctype_digit($attack)) {
			$errorMessage = 'Attack must be a number.';
		}
		elseif (!is_null($defense) && !ctype_digit($defense)) {
			$errorMessage = 'Defense must be a number.';
		}
		elseif (!is_null($range) && !ctype_digit($range)) {
			$errorMessage = 'Range must be a number.';
		}
		elseif (!is_null($weaponLevel) && !ctype_digit($weaponLevel)) {
			$errorMessage = 'Weapon level must be a number.';
		}
		elseif (!is_null($equipLevel) && !ctype_digit($equipLevel)) {
			$errorMessage = 'Equip level must be a number.';
		}
		else {
			if (empty($errorMessage) && is_array($equipLocs)) {
				$locs = FLux::getEquipLocationList();
				foreach ($equipLocs as $bit) {
					if (!array_key_exists($bit, $locs)) {
						$errorMessage = 'Invalid equip location specified.';
						$equipLocs = null;
						break;
					}
				}
			}
			if (empty($errorMessage) && is_array($equipUpper)) {
				$upper = FLux::getEquipUpperList();
				foreach ($equipUpper as $bit) {
					if (!array_key_exists($bit, $upper)) {
						$errorMessage = 'Invalid equip upper specified.';
						$equipUpper = null;
						break;
					}
				}
			}
			if (empty($errorMessage) && is_array($equipJobs)) {
				$jobs = Flux::getEquipJobsList();
				foreach ($equipJobs as $bit) {
					if (!array_key_exists($bit, $jobs)) {
						$errorMessage = 'Invalid equippable job specified.';
						$equipJobs = null;
						break;
					}
				}
			}
			if (empty($errorMessage)) {
				$cols = array('id', 'name_english', 'name_japanese', 'type', 'weight');
				$bind = array($itemID, $identifier, $itemName, $type, $weight*10);
				$vals = array(
					'view'           => $viewID,
					'slots'          => $slots,
					'price_buy'      => $npcBuy,
					'price_sell'     => $npcSell,
					'attack'         => $attack,
					'defence'        => $defense,
					'range'          => $range,
					'weapon_level'   => $weaponLevel,
					'equip_level'    => $equipLevel,
					'script'         => $script,
					'equip_script'   => $equipScript,
					'unequip_script' => $unequipScript,
					'refineable'     => $refineable
				);

				foreach ($vals as $col => $val) {
					if (!is_null($val)) {
						$cols[] = $col;
						$bind[] = $val;
					}
				}

				if ($equipLocs) {
					$bits = 0;
					foreach ($equipLocs as $bit) {
						$bits |= $bit;
					}
					$cols[] = 'equip_locations';
					$bind[] = $bits;
				}

				if ($equipUpper) {
					$bits = 0;
					foreach ($equipUpper as $bit) {
						$bits |= $bit;
					}
					$cols[] = 'equip_upper';
					$bind[] = $bits;
				}

				if ($equipJobs) {
					$bits = 0;
					foreach ($equipJobs as $bit) {
						$bits |= $bit;
					}
					$cols[] = 'equip_jobs';
					$bind[] = $bits;
				}

				$gender = null;
				if ($equipMale && $equipFemale) {
					$gender = 2;
				}
				elseif ($equipMale) {
					$gender = 1;
				}
				elseif ($equipFemale) {
					$gender = 0;
				}

				if (!is_null($gender)) {
					$cols[] = 'equip_genders';
					$bind[] = $gender;
				}

				if ($isCustom) {
					$set = array();
					foreach ($cols as $i => $col) {
						$set[] = "$col = ?";
					}
					
					$sql  = "UPDATE {$server->charMapDatabase}.item_db2 SET ";
					$sql .= implode($set, ', ');
					$sql .= " WHERE id = ?";

					//array_shift($cols);
					//array_shift($bind);
					$bind[] = $itemID;
				}
				else {
					$sql  = "INSERT INTO {$server->charMapDatabase}.item_db2 (".implode(', ', $cols).") ";
					$sql .= "VALUES (".implode(', ', array_fill(0, count($bind), '?')).")";
				}

				$sth = $server->connection->getStatement($sql);
				if ($sth->execute($bind)) {
					$session->setMessageData("Your item '$itemName' ($itemID) has been successfully modified!");
					
					if ($auth->actionAllowed('item', 'view')) {
						$this->redirect($this->url('item', 'view', array('id' => $itemID)));
					}
					else {
						$this->redirect();
					}
				}
				else {
					$errorMessage = 'Failed to modify item!';
				}
			}
		}
	}
}


?>
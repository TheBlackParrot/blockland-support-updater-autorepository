<?php
	header("Content-type: text/plain");

	if(isset($_GET['generateVersion'])) {
		$tmp = [];
		$tmp["version"] = "0.0.0-1";
		die(json_encode($tmp));
	}

	$root = dirname(__FILE__);
	include "$root/config.php";

	if(is_file("$root/order.txt")) {
		// never delete anything in order.txt, if you remove an addon, leave it be
		// otherwise you change addon ID's; this is bad.
		$order_cache = file("$root/order.txt");
		$order_cache = array_map("trim", $order_cache);

		// removing an ADDON FOLDER, however, is fine, the repo generator will take care of it
	} else {
		$order_cache = [];
	}

	$data = [];
	$data["name"] = (isset($setting["name"]) ? $setting["name"] : "Auto Generated Repo");
	$data["add-ons"] = [];

	$top_iter = new DirectoryIterator("$root/" . (isset($setting["addon_folder"]) ? $setting["addon_folder"] : "src"));

	foreach ($top_iter as $addon_dir) {
		if($addon_dir->isFile() || $addon_dir->isDot()) {
			continue;
		}

		$curr_addon_path = $addon_dir->getPathname();
		$curr_addon_name = $addon_dir->getBasename();

		$addon_data = [
			"name" => $curr_addon_name,
			"channels" => []
		];

		if(!in_array($curr_addon_name, $order_cache)) {
			if(count($order_cache) < 1) {
				file_put_contents("$root/order.txt", "$curr_addon_name");
			} else {
				file_put_contents("$root/order.txt", "\n$curr_addon_name", FILE_APPEND);
			}

			$order_cache[] = $curr_addon_name;
		}

		$id = array_search($curr_addon_name, $order_cache);
		$addon_data["id"] = $id;

		$addon_iter = new DirectoryIterator($curr_addon_path);
		foreach ($addon_iter as $channel_dir) {
			if($channel_dir->isFile() || $channel_dir->isDot()) {
				continue;
			}

			$curr_channel_path = $channel_dir->getPathname();
			$curr_channel_name = $channel_dir->getBasename();

			$channel_data = [
				"name" => $curr_channel_name
			];

			if(is_file("$curr_channel_path/description.txt") && !array_key_exists("description", $addon_data)) {
				if(trim(file_get_contents("$curr_channel_path/description.txt")) != "") {
					$addon_data["description"] = trim(file_get_contents("$curr_channel_path/description.txt"));
				}
			}

			if(is_file("$curr_channel_path/version.json")) {
				// TODO: custom "info.json" file to add custom things to "version.json" on the fly

				$version_data = json_decode(file_get_contents("$curr_channel_path/version.json"), true);

				if(!array_key_exists("version", $version_data)) {
					die("version must be pre-defined in version.json: $curr_channel_path\nUse ?generateVersion=1 to get a blank version.json file.");
				}
				$channel_data["version"] = $version_data["version"];

				// going to get clever here and generate parts of version.json on each access

				// clever injection of id's if they do not exist already
				if(!array_key_exists("repositories", $version_data)) {
					$version_data["repositories"] = [[]];
				}
				if(!array_key_exists("id", $version_data["repositories"][0])) {
					$version_data["repositories"][0]["id"] = $id;
				}

				// clever overwriting of channel name
				$version_data["channel"] = $curr_channel_name;

				// clever overwriting of url
				$version_data["repositories"][0]["url"] = "{$setting["root_url"]}/index.php";
				$version_data["repositories"][0]["format"] = "JSON";

				// a.k.a.
				// =====================
				// ===== AUTOMATED =====
				// =====================

				file_put_contents("$curr_channel_path/version.json", json_encode($version_data));
			} else {
				http_response_code(500);
				die("$curr_channel_path: no version.json exists! Can't generate valid repository.\nUse ?generateVersion=1 to get a blank version.json file.");
			}

			$channel_data["file"] = "{$setting["root_url"]}/download.php?id=$id&channel=$curr_channel_name";

			$addon_data["channels"][] = $channel_data;
		}

		$data["add-ons"][] = $addon_data;
	}

	die(json_encode($data));
?>
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

	if(!isset($_GET['id'])) {
		http_response_code(403);
		die("No ID provided");
	}

	if(!ctype_digit($_GET['id'])) {
		http_response_code(403);
		die("Invalid ID");
	}

	$id = $_GET['id'];
	if($id >= count($order_cache)) {
		http_response_code(404);
		die("Add-On ID does not exist");
	}

	$addon = $order_cache[$id];

	if(!is_dir("$root/{$setting['addon_folder']}/$addon")) {
		http_response_code(404);
		die("Add-On no longer exists: $addon ($root/{$setting['addon_folder']}/$addon)");
	}

	if(!ctype_alnum($_GET['channel'])) {
		http_response_code(403);
		die("Invalid channel");
	}

	$channel = $_GET['channel'];

	if(!is_dir("$root/{$setting['addon_folder']}/$addon/$channel")) {
		http_response_code(404);
		die("Channel does not exist for $addon");
	}

	$dl_folder = realpath("$root/{$setting['addon_folder']}/$addon/$channel");

	$zip_dir = "/tmp/BLADDONDL_" . substr(md5(uniqid()), 0, 8);
	if(!mkdir($zip_dir, 0775, true)) {
		http_response_code(500);
		die("Could not generate temporary directory for download: $zip_dir");
	}
	$zip_file = "$zip_dir/$addon.zip";

	// http://stackoverflow.com/a/4914807
	$zip = new ZipArchive();
	$zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

	// Create recursive directory iterator
	/** @var SplFileInfo[] $files */
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dl_folder),
		RecursiveIteratorIterator::LEAVES_ONLY
	);

	foreach ($files as $name => $file) {
		if (!$file->isDir()) {
			$filePath = $file->getRealPath();
			$relativePath = substr($filePath, strlen($dl_folder) + 1);

			$zip->addFile($filePath, $relativePath);
		}
	}

	$zip->close();

	header('Content-Disposition: attachment; filename="' . $addon . '.zip"');
	if(!readfile($zip_file)) {
		http_response_code(500);
		die("Could not read zip file: $zip_file");
	}

	// should delete once the download finishes
	unlink($zip_file);
?>
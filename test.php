<?php
$filePath = './manifest.json';
$manifest = file_get_contents($filePath);
$manifest = json_decode($manifest, true);

print_r($manifest);
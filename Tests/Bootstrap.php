<?php

require_once(__DIR__ . '/../Cache.php');
require_once(__DIR__ . '/../Providers/File.php');
require_once(__DIR__ . '/../Providers/Redis.php');
require_once(__DIR__ . '/../Providers/Uncached.php');

const DEPAGE_CACHE_PATH = "cache";
const DEPAGE_BASE = "base";

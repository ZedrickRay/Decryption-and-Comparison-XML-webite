<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '-1');
set_time_limit(0);
require_once 'PhilHealthEClaimsEncryptor.php';

// Ensure temp directory for extracted files
$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'xml_extract_' . session_id();
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

// Handle file download
if (isset($_GET['download'])) {
    $fileName = basename($_GET['download']);
    $filePath = $tempDir . DIRECTORY_SEPARATOR . $fileName;
    
    if (file_exists($filePath) && is_file($filePath)) {
        $mimeType = 'application/octet-stream';
        if (preg_match('/\.pdf$/i', $fileName)) {
            $mimeType = 'application/pdf';
        } elseif (preg_match('/\.xml$/i', $fileName)) {
            $mimeType = 'application/xml';
        }
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
    http_response_code(404);
    exit;
}


function sanitizeFileName($value) {
    $value = preg_replace('/[\\/:*?"<>|]+/', '_', $value);
    return trim($value, " ._\t\n\r\0\x0B");
}

function isJsonEncryptedPayload($content) {
    if (!is_string($content) || trim($content) === '') {
        return false;
    }
    $decoded = json_decode(trim($content), true);
    return is_array($decoded) && isset($decoded['iv'], $decoded['doc']);
}

function decryptEmbeddedPayload($content, $decryptor, $passphrase) {
    if (!isJsonEncryptedPayload($content)) {
        return false;
    }
    try {
        return $decryptor->decryptPayloadDataToXml($content, $passphrase);
    } catch (Exception $e) {
        return false;
    }
}

function extractDocumentsFromXml($xmlContent, $baseName, $decryptor, $passphrase, &$extractedFiles = [], $depth = 0) {
    preg_match_all('/<OFFLINEDOCUMENT\b([^>]*)>([\s\S]*?)<\/OFFLINEDOCUMENT>/i', $xmlContent, $matches, PREG_SET_ORDER);
    if (!$matches) {
        if ($depth === 0) {
            // No documents found at root level
        }
        return;
    }

    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'xml_extract_' . session_id();

    foreach ($matches as $index => $match) {
        $attrs = $match[1];
        $base64 = preg_replace('/\s+/', '', $match[2]);
        $decoded = base64_decode($base64, true);

        if ($decoded === false) {
            continue;
        }

        $docType = 'doc' . ($index + 1);
        if (preg_match('/pDocumentType="([^"]+)"/i', $attrs, $m)) {
            $docType = sanitizeFileName($m[1]);
        }

        $mimeType = 'application/octet-stream';
        if (preg_match('/pMimeType="([^"]+)"/i', $attrs, $m)) {
            $mimeType = $m[1];
        }

        $encryptedFlag = strtoupper('N');
        if (preg_match('/pEncryptionUsed="([^"]+)"/i', $attrs, $m)) {
            $encryptedFlag = strtoupper($m[1]);
        }

        if ($encryptedFlag === 'Y' || isJsonEncryptedPayload($decoded)) {
            $decrypted = decryptEmbeddedPayload($decoded, $decryptor, $passphrase);
            if ($decrypted !== false) {
                $decoded = $decrypted;
            }
        }

        $ext = 'bin';
        if (stripos($mimeType, 'pdf') !== false) {
            $ext = 'pdf';
        } elseif (stripos($mimeType, 'xml') !== false) {
            $ext = 'xml';
        } elseif (strpos($decoded, '%PDF') !== false) {
            $ext = 'pdf';
        } elseif (strpos(ltrim($decoded), '<?xml') === 0) {
            $ext = 'xml';
        }

        if ($ext === 'pdf' && ($pdfOffset = strpos($decoded, '%PDF')) !== false && $pdfOffset > 0) {
            $decoded = substr($decoded, $pdfOffset);
        }

        $fileName = sprintf('%s_%s_%d.%s', $baseName, $docType, $index + 1, $ext);
        $filePath = $tempDir . DIRECTORY_SEPARATOR . $fileName;
        file_put_contents($filePath, $decoded);
        
        $extractedFiles[] = [
            'name' => $fileName,
            'path' => $filePath,
            'mimeType' => $mimeType,
            'size' => strlen($decoded),
            'ext' => $ext
        ];

        if ($ext === 'xml') {
            $content = ltrim($decoded);
            if (stripos($content, '<OFFLINEDOCUMENT') !== false || stripos($content, '<OFFLINEDOCUMENTS') !== false) {
                extractDocumentsFromXml($decoded, pathinfo($fileName, PATHINFO_FILENAME), $decryptor, $passphrase, $extractedFiles, $depth + 1);
            }
        }
    }
}

function loadXmlFromString($xml, $label, &$errors)
{
    $doc = new DOMDocument();
    $doc->preserveWhiteSpace = false;
    $doc->formatOutput = false;
    $doc->strictErrorChecking = false;

    libxml_use_internal_errors(true);
    $success = $doc->loadXML($xml, LIBXML_NONET | LIBXML_NOCDATA);
    if (! $success) {
        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf('%s parse error: %s on line %d', $label, trim($error->message), $error->line);
        }
        libxml_clear_errors();
        return null;
    }

    $doc->normalizeDocument();
    return $doc;
}

function compareNodes($a, $b, $path, &$diffs, $nameA, $nameB)
{
    if ($a === null && $b === null) {
        return;
    }
    if ($a === null) {
        $diffs[] = "Missing in $nameA: $path";
        return;
    }
    if ($b === null) {
        $diffs[] = "Missing in $nameB: $path";
        return;
    }
    if ($a->nodeName !== $b->nodeName) {
        $diffs[] = "Node mismatch at $path: {$a->nodeName} vs {$b->nodeName}";
    }

    compareAttributes($a, $b, $path, $diffs, $nameA, $nameB);

    $textA = normalizeText(getNodeText($a));
    $textB = normalizeText(getNodeText($b));
    $childrenA = getElementChildren($a);
    $childrenB = getElementChildren($b);

    if (empty($childrenA) && empty($childrenB)) {
        if ($textA !== $textB) {
            $diffs[] = "Value difference at $path: '$textA' vs '$textB'";
        }
        return;
    }

    $count = max(count($childrenA), count($childrenB));
    for ($i = 0; $i < $count; $i++) {
        $childA = $childrenA[$i] ?? null;
        $childB = $childrenB[$i] ?? null;
        if ($childA && $childB && $childA->nodeName === $childB->nodeName) {
            $childPath = "$path/{$childA->nodeName}" . '[' . ($i + 1) . ']';
        } else {
            $nameAChild = $childA ? $childA->nodeName : 'missing';
            $nameBChild = $childB ? $childB->nodeName : 'missing';
            $childPath = "$path/$nameAChild/$nameBChild" . '[' . ($i + 1) . ']';
        }
        compareNodes($childA, $childB, $childPath, $diffs, $nameA, $nameB);
    }
}

function compareAttributes($a, $b, $path, &$diffs, $nameA, $nameB)
{
    $attrsA = [];
    foreach ($a->attributes as $attr) {
        $attrsA[$attr->name] = $attr->value;
    }
    $attrsB = [];
    foreach ($b->attributes as $attr) {
        $attrsB[$attr->name] = $attr->value;
    }
    $all = array_unique(array_merge(array_keys($attrsA), array_keys($attrsB)));
    sort($all);

    foreach ($all as $name) {
        $hasA = array_key_exists($name, $attrsA);
        $hasB = array_key_exists($name, $attrsB);
        if ($hasA && ! $hasB) {
            $diffs[] = "Attribute missing in $nameB at $path:@$name (value='{$attrsA[$name]}')";
            continue;
        }
        if (! $hasA && $hasB) {
            $diffs[] = "Attribute missing in $nameA at $path:@$name (value='{$attrsB[$name]}')";
            continue;
        }
        if ($hasA && $hasB && $attrsA[$name] !== $attrsB[$name]) {
            $diffs[] = "Attribute changed at $path:@$name: '{$attrsA[$name]}' vs '{$attrsB[$name]}'";
        }
    }
}

function getElementChildren($node)
{
    $children = [];
    foreach ($node->childNodes as $child) {
        if ($child instanceof DOMElement) {
            $children[] = $child;
        }
    }
    return $children;
}

function getNodeText($node)
{
    $text = '';
    foreach ($node->childNodes as $child) {
        if ($child instanceof DOMText || $child instanceof DOMCdataSection) {
            $text .= $child->wholeText;
        }
    }
    return $text;
}

function normalizeText($value)
{
    return preg_replace('/\s+/u', ' ', trim($value));
}

function h($value)
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function diffLines($aLines, $bLines)
{
    $n = count($aLines);
    $m = count($bLines);
    $dp = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
    for ($i = $n - 1; $i >= 0; $i--) {
        for ($j = $m - 1; $j >= 0; $j--) {
            if ($aLines[$i] === $bLines[$j]) {
                $dp[$i][$j] = 1 + $dp[$i + 1][$j + 1];
            } else {
                $dp[$i][$j] = $dp[$i + 1][$j] >= $dp[$i][$j + 1] ? $dp[$i + 1][$j] : $dp[$i][$j + 1];
            }
        }
    }

    $i = 0;
    $j = 0;
    $ops = [];
    while ($i < $n && $j < $m) {
        if ($aLines[$i] === $bLines[$j]) {
            $ops[] = ['equal', $aLines[$i]];
            $i++;
            $j++;
        } elseif ($dp[$i + 1][$j] >= $dp[$i][$j + 1]) {
            $ops[] = ['delete', $aLines[$i]];
            $i++;
        } else {
            $ops[] = ['insert', $bLines[$j]];
            $j++;
        }
    }
    while ($i < $n) {
        $ops[] = ['delete', $aLines[$i]];
        $i++;
    }
    while ($j < $m) {
        $ops[] = ['insert', $bLines[$j]];
        $j++;
    }

    return $ops;
}

function highlightXmlDifferences($textA, $textB)
{
    $linesA = preg_split('/\r?\n/', $textA);
    $linesB = preg_split('/\r?\n/', $textB);
    $ops = diffLines($linesA, $linesB);
    $html = '';

    foreach ($ops as $op) {
        list($type, $line) = $op;
        $escaped = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if ($type === 'equal') {
            $html .= '<span class="diff-line">' . $escaped . '</span>\n';
        } elseif ($type === 'delete') {
            $html .= '<span class="diff-line diff-removed">' . $escaped . '</span>\n';
        } elseif ($type === 'insert') {
            $html .= '<span class="diff-line diff-added">' . $escaped . '</span>\n';
        }
    }

    return $html;
}

$errors = [];
$diffs = [];
$uploaded = false;
$decryptUploaded = false;
$decryptErrors = [];
$decryptResult = false;
$decryptedXml = '';
$decryptFileName = '';
$decryptPassphrase = 'YSQhNIRYCXiRqWONOxkhtkwyl';
$extractedFiles = [];
$xmlA = '';
$xmlB = '';
$formattedA = '';
$formattedB = '';
$formattedAHtml = '';
$formattedBHtml = '';
$nameA = 'First XML file';
$nameB = 'Second XML file';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'decrypt') {
        $decryptUploaded = true;
        $decryptPassphrase = trim($_POST['passphrase'] ?? $decryptPassphrase);

        if (empty($_FILES['encFile']['tmp_name'])) {
            $decryptErrors[] = 'Upload an encrypted file to decrypt.';
        } elseif ($_FILES['encFile']['error'] !== UPLOAD_ERR_OK) {
            $decryptErrors[] = 'Error uploading file.';
        }

        if (empty($decryptErrors)) {
            $encrypted = file_get_contents($_FILES['encFile']['tmp_name']);
            $inputFileName = $_FILES['encFile']['name'];
            $decryptor = new PhilHealthEClaimsEncryptor();
            $decryptedXml = false;
            
            try {
                // Try direct decryption (for .enc files)
                $decryptedXml = $decryptor->decryptPayloadDataToXml($encrypted, $decryptPassphrase);
                
                // If that fails and it's a .xml file, check for embedded encrypted payloads
                if (($decryptedXml === false || $decryptedXml === '') && preg_match('/\.xml$/i', $inputFileName)) {
                    $xmlContent = trim($encrypted);
                    
                    // Check if the entire XML contains JSON-like encrypted payload
                    if (isJsonEncryptedPayload($xmlContent)) {
                        $decryptedXml = decryptEmbeddedPayload($xmlContent, $decryptor, $decryptPassphrase);
                    }
                    
                    // If still no luck, try extracting embedded encrypted documents
                    if ($decryptedXml === false || $decryptedXml === '') {
                        // Parse as XML and try to extract and decrypt embedded documents
                        $doc = new DOMDocument();
                        $doc->preserveWhiteSpace = false;
                        libxml_use_internal_errors(true);
                        if (@$doc->loadXML($xmlContent, LIBXML_NONET | LIBXML_NOCDATA)) {
                            // If we can load it, it's already decrypted XML, use it as is
                            $decryptedXml = $xmlContent;
                        }
                        libxml_clear_errors();
                    }
                }
                
                if ($decryptedXml === false || $decryptedXml === '') {
                    $decryptErrors[] = 'Decryption failed or returned empty content.';
                } else {
                    $decryptResult = true;
                    $decryptFileName = preg_replace('/\.enc$/i', '.xml', $inputFileName);
                    if ($decryptFileName === $inputFileName) {
                        $decryptFileName = preg_replace('/\.xml$/i', '_decrypted.xml', $inputFileName);
                    }
                    $baseName = pathinfo($decryptFileName, PATHINFO_FILENAME);
                    extractDocumentsFromXml($decryptedXml, $baseName, $decryptor, $decryptPassphrase, $extractedFiles);
                }
            } catch (Exception $e) {
                $decryptErrors[] = 'Decryption error: ' . $e->getMessage();
            }
        }
    } else {
        $uploaded = true;

        if (empty($_FILES['xmlA']['tmp_name']) || empty($_FILES['xmlB']['tmp_name'])) {
            $errors[] = 'Upload both XML files to compare.';
        } else {
            if ($_FILES['xmlA']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Error uploading first file.';
            }
            if ($_FILES['xmlB']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Error uploading second file.';
            }
        }

        if (empty($errors)) {
            $xmlA = file_get_contents($_FILES['xmlA']['tmp_name']);
            $xmlB = file_get_contents($_FILES['xmlB']['tmp_name']);
            $nameA = $_FILES['xmlA']['name'];
            $nameB = $_FILES['xmlB']['name'];

            $docA = loadXmlFromString($xmlA, $nameA, $errors);
            $docB = loadXmlFromString($xmlB, $nameB, $errors);

            if ($docA && $docB) {
                compareNodes($docA->documentElement, $docB->documentElement, '/' . $docA->documentElement->nodeName, $diffs, $nameA, $nameB);

                $docA->preserveWhiteSpace = false;
                $docA->formatOutput = true;
                $docA->normalizeDocument();
                $formattedA = $docA->saveXML();
                $formattedA = preg_replace('/^<\?xml.*?\?>\s*/', '', $formattedA);

                $docB->preserveWhiteSpace = false;
                $docB->formatOutput = true;
                $docB->normalizeDocument();
                $formattedB = $docB->saveXML();

                $formattedAHtml = highlightXmlDifferences($formattedA, $formattedB);
                $formattedBHtml = highlightXmlDifferences($formattedB, $formattedA);
            }
        }
    }
}
?>
<?php include 'xml_compare_ui.php'; ?>

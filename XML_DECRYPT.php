<?php

require_once "PhilHealthEClaimsEncryptor.php";

function generateOutputName($inputFile) {
    $output = preg_replace('/\.enc$/i', '', $inputFile);
    if ($output === $inputFile) {
        $output .= ".xml";
    }
    return $output;
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

function extractDocumentsFromXml($xmlContent, $baseName, $decryptor, $passphrase) {
    preg_match_all('/<OFFLINEDOCUMENT\b([^>]*)>([\s\S]*?)<\/OFFLINEDOCUMENT>/i', $xmlContent, $matches, PREG_SET_ORDER);
    if (!$matches) {
        echo "ℹ️ No embedded documents found in {$baseName}\n";
        return;
    }

    foreach ($matches as $index => $match) {
        $attrs = $match[1];
        $base64 = preg_replace('/\s+/', '', $match[2]);
        $decoded = base64_decode($base64, true);

        if ($decoded === false) {
            echo "⚠️ OFFLINEDOCUMENT base64 decode failed\n";
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
                echo "🔐 Decrypted embedded document {$docType}\n";
            } else {
                echo "⚠️ Could not decrypt embedded document {$docType}, saving raw output\n";
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
        file_put_contents($fileName, $decoded);
        echo "✅ Extracted {$fileName} ({$mimeType})\n";

        if ($ext === 'xml') {
            $content = ltrim($decoded);
            if (stripos($content, '<OFFLINEDOCUMENT') !== false || stripos($content, '<OFFLINEDOCUMENTS') !== false) {
                extractDocumentsFromXml($decoded, pathinfo($fileName, PATHINFO_FILENAME), $decryptor, $passphrase);
            }
        }
    }
}

function getInputFiles($arg) {
    if ($arg === null) {
        return glob('*.xml.enc') ?: [];
    }
    if (is_dir($arg)) {
        return glob(rtrim($arg, '\\/') . DIRECTORY_SEPARATOR . '*.xml.enc') ?: [];
    }
    if (is_file($arg)) {
        return [$arg];
    }
    return [];
}

try {
    // 🔹 Input file (hard-coded default, or pass as argument)
    $inputFile = $argv[1] ?? "Export_Data_35447305082026.enc";
    $passphrase = "YSQhNIRYCXiRqWONOxkhtkwyl";

    if (!file_exists($inputFile)) {
        throw new Exception("File not found: {$inputFile}");
    }

    $decryptor = new PhilHealthEClaimsEncryptor();

    echo "🔓 Decrypting {$inputFile}...\n";

    // ✅ IMPORTANT: read file content
    $encryptedContent = file_get_contents($inputFile);
    $xmlContent = $decryptor->decryptPayloadDataToXml($encryptedContent, $passphrase);

    if (empty($xmlContent)) {
        throw new Exception("Decryption returned empty output for {$inputFile}");
    }

    $outputXml = generateOutputName($inputFile);
    file_put_contents($outputXml, $xmlContent);
    echo "✅ XML saved: {$outputXml}\n";

    $baseName = pathinfo($outputXml, PATHINFO_FILENAME);
    extractDocumentsFromXml($xmlContent, $baseName, $decryptor, $passphrase);

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
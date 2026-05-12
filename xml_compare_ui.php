<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import & Compare XML</title>
    <style>
        :root {
            color-scheme: dark;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 16px;
            line-height: 1.5;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: url("assets/anime-dragon-ball-black-goku-wallpaper-preview.jpg") center center / cover no-repeat fixed;
            color: #e2e8f0;
        }

        .page-shell {
            max-width: 3000px;
            margin: 0 auto;
            padding: 28px 20px 48px;
        }

        .hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            padding: 36px 34px;
            border-radius: 28px;
            border: 1px solid rgba(148, 163, 184, 0.12);
            background: rgba(15, 23, 42, 0.92);
            box-shadow: 0 40px 120px rgba(15, 23, 42, 0.28);
            backdrop-filter: blur(14px);
        }

        .hero-copy {
            max-width: 820px;
        }

        .hero-copy h1 {
            margin: 0;
            font-size: clamp(2.4rem, 3vw, 4rem);
            letter-spacing: -0.04em;
            color: #f8fafc;
        }

        .hero-copy p {
            margin: 20px 0 0;
            max-width: 760px;
            color: #cbd5e1;
            font-size: 1.03rem;
        }

        .hero-meta {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 999px;
            background: rgba(59, 130, 246, 0.14);
            color: #bfdbfe;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .content-grid {
            display: grid;
            gap: 24px;
            margin-top: 28px;
        }

        .card {
            border-radius: 26px;
            border: 1px solid rgba(148, 163, 184, 0.12);
            background: rgba(15, 23, 42, 0.94);
            box-shadow: 0 30px 90px rgba(15, 23, 42, 0.22);
            padding: 30px;
        }

        .card-header {
            margin: 0 0 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            color: #f8fafc;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.3rem;
        }

        .card-header p {
            margin: 0;
            color: #94a3b8;
            font-size: 0.98rem;
        }

        .file-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .file-input {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 18px;
            border-radius: 20px;
            border: 1px dashed rgba(148, 163, 184, 0.25);
            background: rgba(30, 41, 59, 0.82);
        }

        .file-input label {
            color: #e2e8f0;
            font-weight: 700;
            font-size: 0.98rem;
        }

        input[type=file] {
            width: 100%;
            color: #cbd5e1;
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 16px;
            padding: 14px 16px;
            cursor: pointer;
        }

        input[type=file]::-webkit-file-upload-button {
            border: none;
            background: #2563eb;
            color: #fff;
            padding: 10px 16px;
            border-radius: 12px;
            cursor: pointer;
        }

        .actions {
            display: flex;
            justify-content: flex-start;
        }

        button {
            background: linear-gradient(135deg, #38bdf8 0%, #2563eb 100%);
            border: none;
            color: white;
            padding: 16px 26px;
            border-radius: 999px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 700;
            transition: transform 0.18s ease, filter 0.18s ease;
            width: fit-content;
        }

        button:hover {
            transform: translateY(-1px);
            filter: brightness(1.05);
        }

        .message {
            border-radius: 22px;
            padding: 24px;
            line-height: 1.65;
            font-size: 0.98rem;
        }

        .success {
            background: rgba(16, 185, 129, 0.14);
            border: 1px solid rgba(34, 197, 94, 0.28);
            color: #d1fae5;
        }

        .error {
            background: rgba(248, 113, 113, 0.16);
            border: 1px solid rgba(251, 191, 36, 0.22);
            color: #fee2e2;
        }

        .message strong {
            display: block;
            margin-bottom: 10px;
            font-size: 1.05rem;
            color: #f8fafc;
        }

        .message ul {
            margin: 0;
            padding-left: 20px;
        }

        .message li {
            margin-bottom: 10px;
            color: #cbd5e1;
        }

        .preview-panel {
            display: grid;
            gap: 24px;
        }

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            padding-bottom: 4px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.16);
        }

        .preview-header h2 {
            margin: 0;
            font-size: 1.15rem;
            color: #f8fafc;
        }

        .preview-header p {
            margin: 2px 0 0;
            color: #94a3b8;
            font-size: 0.98rem;
        }

        .split-view {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .panel {
            display: flex;
            flex-direction: column;
            gap: 18px;
            background: rgba(15, 23, 42, 0.96);
            border: 1px solid rgba(148, 163, 184, 0.12);
            border-radius: 24px;
            padding: 22px;
        }

        .panel h3 {
            margin: 0;
            font-size: 1rem;
            color: #f8fafc;
        }

        .panel-content {
            display: flex;
            gap: 18px;
            align-items: flex-start;
        }

        .panel-actions {
            flex: 0 0 220px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            gap: 14px;
        }

        .xml-view {
            flex: 0 0 auto;
            width: 800px;
            height: 380px;
            background: #0f172a;
            border-radius: 18px;
            color: #e2e8f0;
            padding: 22px;
            overflow: auto;
            white-space: pre-wrap;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.95rem;
            line-height: 1.55;
            border: 1px solid rgba(148, 163, 184, 0.14);
        }

        .view-button {
            width: auto;
            margin-top: 0;
            padding: 12px 20px;
            font-size: 0.9rem;
        }

        @media (max-width: 1024px) {
            .panel-content {
                flex-direction: column;
            }

            .panel-actions {
                width: 100%;
                flex: none;
            }
        }

        @media (max-width: 1024px) {
            .hero {
                flex-direction: column;
                align-items: flex-start;
            }

            .file-grid,
            .split-view {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .page-shell {
                padding: 22px 16px 34px;
            }

            .hero {
                padding: 24px;
            }

            .card {
                padding: 24px;
            }

            button {
                width: 100%;
            }
        }
    </style>
    <script>
        function openXmlPreview(panelId, title) {
            var content = document.getElementById(panelId + 'Html').innerHTML;
            var preview = window.open('', '_blank');
            if (!preview) {
                alert('Unable to open preview window. Please allow popups for this site.');
                return;
            }
            preview.document.write('<!doctype html><html><head><title>' + title + '</title><style>body{margin:0;padding:24px;background:#020617;color:#e2e8f0;font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;white-space:pre-wrap;word-break:break-word;}h1{margin:0 0 18px;font-size:1.8rem;color:#60a5fa;}pre{background:#0f172a;color:#e2e8f0;padding:24px;border-radius:20px;overflow:auto;max-height:calc(100vh - 96px);box-sizing:border-box;}.diff-removed{background-color:#fee2e2;color:#dc2626;}.diff-added{background-color:#dcfce7;color:#16a34a;}.diff-line{display:block;}</style></head><body><h1>' + title + '</h1><pre>' + content + '</pre></body></html>');
            preview.document.close();
        }
    </script>
</head>
<body>
<div class="page-shell">
    <section class="hero">
        <div class="hero-copy">
            <span class="hero-meta">XML Comparison Decryption Studio</span>
            <h1>Compare and Decrypt XML files instantly</h1>
            <p>Upload two XML files to highlight structural changes, value differences, and attribute mismatches with a polished review experience.</p>
        </div>
        <div class="hero-meta">Upload. Compare. Review.</div>
    </section>

    <main class="content-grid">
        <section class="card upload-card">
            <div class="card-header">
                <div>
                    <h2>Upload files</h2>
                    <p>Select two XML documents for a fast side-by-side comparison.</p>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data">
                <div class="file-grid">
                    <div class="file-input">
                        <label for="xmlA">First XML file</label>
                        <input type="file" id="xmlA" name="xmlA" accept=".xml,text/xml" required>
                    </div>
                    <div class="file-input">
                        <label for="xmlB">Second XML file</label>
                        <input type="file" id="xmlB" name="xmlB" accept=".xml,text/xml" required>
                    </div>
                </div>
                <div class="actions">
                    <button type="submit">Compare files</button>
                </div>
            </form>
        </section>

        <section class="card decrypt-card">
            <div class="card-header">
                <div>
                    <h2>Decrypt XML</h2>
                    <p>Upload an encrypted .enc or .xml file with embedded encrypted payloads and provide the passphrase to decrypt it.</p>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data">
                <div class="file-grid" style="grid-template-columns: 1fr;">
                    <div class="file-input">
                        <label for="encFile">Encrypted file (.enc or .xml)</label>
                        <input type="file" id="encFile" name="encFile" accept=".enc,.xml.enc,.xml,text/xml" required>
                    </div>
                </div>
                <div class="file-input">
                    <label for="passphrase">Decryption Passphrase</label>
                    <input type="password" id="passphrase" name="passphrase" placeholder="Enter passphrase" style="
                        color: #cbd5e1;
                        background: rgba(15, 23, 42, 0.85);
                        border: 1px solid rgba(148, 163, 184, 0.22);
                        border-radius: 16px;
                        padding: 14px 16px;
                        cursor: text;
                        width: 100%;
                        font-family: inherit;
                        font-size: 1rem;
                    ">
                </div>
                <div class="actions">
                    <input type="hidden" name="action" value="decrypt">
                    <button type="submit">Decrypt file</button>
                </div>
            </form>
        </section>

        <?php if ($decryptUploaded): ?>
            <section class="card status-card">
                <div class="card-header">
                    <div>
                        <h2>Decryption results</h2>
                        <p>Review the decrypted XML content below.</p>
                    </div>
                </div>

                <?php if (!empty($decryptErrors)): ?>
                    <div class="message error">
                        <strong>Decryption error</strong>
                        <ul>
                            <?php foreach ($decryptErrors as $error): ?>
                                <li><?= h($error) ?></li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                <?php elseif ($decryptResult): ?>
                    <div class="message success">
                        <strong>✅ Decryption successful</strong>
                        <p>Your encrypted file has been decrypted to XML. Now extracting all embedded documents...</p>
                    </div>

                    <?php if (!empty($extractedFiles)): ?>
                        <div class="message success" style="background: rgba(16, 185, 129, 0.24);">
                            <strong>📦 Extraction complete - <?= count($extractedFiles) ?> file(s) found</strong>
                            <p>All content files have been extracted from the decrypted XML and are ready for download below.</p>
                        </div>
                    <?php else: ?>
                        <div class="message" style="background: rgba(100, 116, 139, 0.14); color: #cbd5e1;">
                            <strong>ℹ️ No embedded documents found</strong>
                            <p>The decrypted XML does not contain any embedded files within OFFLINEDOCUMENT tags.</p>
                        </div>
                    <?php endif ?>

                    <div class="preview-panel">
                        <div class="preview-header">
                            <div>
                                <h2>Decrypted XML</h2>
                                <p><?= h($decryptFileName) ?></p>
                            </div>
                        </div>

                        <div class="panel">
                            <div class="panel-content">
                                <pre id="decryptedXmlPanel" class="xml-view"><?= h($decryptedXml) ?></pre>
                                <div class="panel-actions">
                                    <button type="button" class="view-button" onclick="downloadDecrypted()">Download XML</button>
                                    <button type="button" class="view-button" onclick="copyToClipboard('decryptedXmlPanel')">Copy to clipboard</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        function downloadDecrypted() {
                            var content = document.getElementById('decryptedXmlPanel').innerText;
                            var blob = new Blob([content], { type: 'application/xml' });
                            var url = window.URL.createObjectURL(blob);
                            var a = document.createElement('a');
                            a.href = url;
                            a.download = '<?= h($decryptFileName) ?>';
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(a);
                        }

                        function copyToClipboard(elementId) {
                            var text = document.getElementById(elementId).innerText;
                            navigator.clipboard.writeText(text).then(function() {
                                alert('XML copied to clipboard!');
                            }).catch(function(err) {
                                alert('Failed to copy: ' + err);
                            });
                        }
                    </script>

                    <?php if (!empty($extractedFiles)): ?>
                        <div class="preview-panel" style="margin-top: 28px;">
                            <div class="preview-header">
                                <div>
                                    <h2>📁 Extracted Content Files</h2>
                                    <p>These files were extracted from inside the decrypted XML file. Click to download any of them.</p>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
                                <?php foreach ($extractedFiles as $file): ?>
                                    <div style="
                                        background: rgba(30, 41, 59, 0.82);
                                        border: 1px solid rgba(148, 163, 184, 0.22);
                                        border-radius: 20px;
                                        padding: 18px;
                                        display: flex;
                                        flex-direction: column;
                                        gap: 14px;
                                    ">
                                        <div>
                                            <div style="color: #e2e8f0; font-weight: 700; font-size: 0.98rem; word-break: break-word;">
                                                <?= h($file['name']) ?>
                                            </div>
                                            <div style="color: #94a3b8; font-size: 0.85rem; margin-top: 6px;">
                                                <?= strtoupper($file['ext']) ?> • <?= number_format($file['size']) ?> bytes
                                            </div>
                                        </div>
                                        <a href="?download=<?= urlencode($file['name']) ?>" style="
                                            display: inline-block;
                                            background: linear-gradient(135deg, #38bdf8 0%, #2563eb 100%);
                                            color: white;
                                            padding: 10px 16px;
                                            border-radius: 999px;
                                            text-decoration: none;
                                            text-align: center;
                                            font-weight: 700;
                                            font-size: 0.9rem;
                                            transition: transform 0.18s ease, filter 0.18s ease;
                                        " onmouseover="this.style.transform='translateY(-1px)'; this.style.filter='brightness(1.05)';" onmouseout="this.style.transform='translateY(0)'; this.style.filter='brightness(1)';">
                                            Download
                                        </a>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    <?php endif ?>
                <?php endif ?>
            </section>
        <?php endif ?>

        <?php if ($uploaded): ?>
            <section class="card status-card">
                <div class="card-header">
                    <div>
                        <h2>Comparison results</h2>
                        <p>Review upload status and difference details for the selected files.</p>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="message error">
                        <strong>Upload error</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= h($error) ?></li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="message <?= empty($diffs) ? 'success' : 'error' ?>">
                        <strong><?= empty($diffs) ? 'No differences found' : 'Differences found' ?></strong>
                        <?php if (empty($diffs)): ?>
                            <p>Both XML files are structurally equal and contain the same values for compared nodes.</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($diffs as $diff): ?>
                                    <li><?= h($diff) ?></li>
                                <?php endforeach ?>
                            </ul>
                        <?php endif ?>
                    </div>

                    <div class="preview-panel">
                        <div class="preview-header">
                            <div>
                                <h2>XML Preview</h2>
                                <p>Compare the formatted XML content for each file, and open a full zoomed view when needed.</p>
                            </div>
                        </div>

                        <div class="split-view">
                            <div class="panel">
                                <h3><?= h($nameA) ?></h3>
                                <div class="panel-content">
                                    <pre id="xmlPanelA" class="xml-view"><?= h($formattedA !== '' ? $formattedA : $xmlA) ?></pre>
                                    <div id="xmlPanelAHtml" style="display:none;"><?php echo $formattedAHtml; ?></div>
                                    <div class="panel-actions">
                                        <button type="button" class="view-button" onclick="openXmlPreview('xmlPanelA', '<?= h($nameA) ?>')">View full XML</button>
                                        <button type="button" class="view-button" onclick="document.getElementById('xmlPanelA').innerText = ''; document.getElementById('xmlA').value = '';">Remove</button>
                                    </div>
                                </div>
                            </div>
                            <div class="panel">
                                <h3><?= h($nameB) ?></h3>
                                <div class="panel-content">
                                    <pre id="xmlPanelB" class="xml-view"><?= h($formattedB !== '' ? $formattedB : $xmlB) ?></pre>
                                    <div id="xmlPanelBHtml" style="display:none;"><?php echo $formattedBHtml; ?></div>
                                    <div class="panel-actions">
                                        <button type="button" class="view-button" onclick="openXmlPreview('xmlPanelB', '<?= h($nameB) ?>')">View full XML</button>
                                        <button type="button" class="view-button" onclick="document.getElementById('xmlPanelB').innerText = ''; document.getElementById('xmlB').value = '';">Remove</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif ?>
            </section>
        <?php endif ?>
    </main>
</div>
</body>
</html>
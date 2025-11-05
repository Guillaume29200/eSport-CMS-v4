<!-- eSport-CMS V4 - Debug Bar -->
<style>
    #esport-debug-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #1a1a1a;
        color: #00ff00;
        font-family: 'Courier New', 'Monaco', monospace;
        font-size: 12px;
        border-top: 3px solid #00ff00;
        z-index: 999999;
        max-height: 500px;
        box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.5);
    }
    
    #esport-debug-bar * {
        box-sizing: border-box;
    }
    
    #esport-debug-bar .debug-header {
        padding: 12px 20px;
        background: #0d0d0d;
        border-bottom: 1px solid #333;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    #esport-debug-bar .debug-stats {
        display: flex;
        gap: 30px;
    }
    
    #esport-debug-bar .debug-stat {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    #esport-debug-bar .debug-close {
        background: #ff0000;
        color: #fff;
        border: none;
        padding: 5px 15px;
        cursor: pointer;
        border-radius: 3px;
        font-size: 11px;
    }
    
    #esport-debug-bar .debug-close:hover {
        background: #cc0000;
    }
    
    #esport-debug-bar .tab-buttons {
        display: flex;
        background: #0d0d0d;
        border-bottom: 1px solid #333;
    }
    
    #esport-debug-bar .tab-button {
        padding: 12px 25px;
        cursor: pointer;
        border-right: 1px solid #333;
        background: #0d0d0d;
        color: #888;
        transition: all 0.2s;
        border-bottom: 3px solid transparent;
    }
    
    #esport-debug-bar .tab-button:hover {
        background: #1a1a1a;
        color: #00ff00;
    }
    
    #esport-debug-bar .tab-button.active {
        background: #1a1a1a;
        color: #00ff00;
        border-bottom-color: #00ff00;
    }
    
    #esport-debug-bar .tab-content {
        padding: 15px 20px;
        display: none;
        max-height: 350px;
        overflow-y: auto;
    }
    
    #esport-debug-bar .tab-content.active {
        display: block;
    }
    
    #esport-debug-bar .query-item {
        margin-bottom: 15px;
        padding: 10px;
        background: #0d0d0d;
        border-left: 3px solid #00ff00;
    }
    
    #esport-debug-bar .query-item.slow {
        border-left-color: #ff9800;
    }
    
    #esport-debug-bar .query-meta {
        color: #888;
        font-size: 11px;
        margin-bottom: 5px;
    }
    
    #esport-debug-bar .query-sql {
        color: #00ff00;
        word-break: break-all;
    }
    
    #esport-debug-bar .query-params {
        color: #888;
        font-size: 11px;
        margin-top: 5px;
    }
    
    #esport-debug-bar .security-check {
        padding: 8px 10px;
        margin-bottom: 5px;
        background: #0d0d0d;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    #esport-debug-bar .security-check.passed {
        border-left: 3px solid #4caf50;
    }
    
    #esport-debug-bar .security-check.failed {
        border-left: 3px solid #f44336;
    }
    
    #esport-debug-bar .log-item {
        padding: 8px 10px;
        margin-bottom: 5px;
        background: #0d0d0d;
    }
    
    #esport-debug-bar .log-level {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 10px;
        font-weight: bold;
        margin-right: 10px;
    }
    
    #esport-debug-bar .log-level.DEBUG { background: #2196f3; color: #fff; }
    #esport-debug-bar .log-level.INFO { background: #4caf50; color: #fff; }
    #esport-debug-bar .log-level.WARNING { background: #ff9800; color: #000; }
    #esport-debug-bar .log-level.ERROR { background: #f44336; color: #fff; }
    #esport-debug-bar .log-level.CRITICAL { background: #9c27b0; color: #fff; }
    
    #esport-debug-bar .file-item {
        padding: 5px 10px;
        color: #888;
        font-size: 11px;
        border-bottom: 1px solid #222;
    }
    
    #esport-debug-bar .stat-warning {
        color: #ff9800;
    }
    
    #esport-debug-bar .stat-error {
        color: #f44336;
    }
    
    #esport-debug-bar .stat-success {
        color: #4caf50;
    }
    
    /* Scrollbar custom */
    #esport-debug-bar ::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }
    
    #esport-debug-bar ::-webkit-scrollbar-track {
        background: #0d0d0d;
    }
    
    #esport-debug-bar ::-webkit-scrollbar-thumb {
        background: #333;
        border-radius: 5px;
    }
    
    #esport-debug-bar ::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>

<div id="esport-debug-bar">
    <!-- Header avec stats rapides -->
    <div class="debug-header">
        <div class="debug-stats">
            <div class="debug-stat">
                <strong>🐛 DEV MODE</strong>
            </div>
            <div class="debug-stat">
                ⏱️ <strong><?= number_format($loadTime, 3) ?>s</strong>
            </div>
            <div class="debug-stat">
                💾 <strong><?= number_format($memoryUsage, 2) ?>MB</strong> / <?= $memoryLimit ?>
                <?php if ($memoryPeak > $memoryUsage * 1.5): ?>
                    <span class="stat-warning">(Peak: <?= number_format($memoryPeak, 2) ?>MB)</span>
                <?php endif; ?>
            </div>
            <div class="debug-stat">
                🗄️ <strong><?= $queriesCount ?></strong> queries
                <?php if (count($slowQueries) > 0): ?>
                    <span class="stat-warning">(<?= count($slowQueries) ?> slow!)</span>
                <?php endif; ?>
                <span class="stat-warning">(<?= number_format($totalQueryTime, 3) ?>s total)</span>
            </div>
            <div class="debug-stat">
                🔧 <strong><?= $filesCount ?></strong> files
            </div>
        </div>
        <button class="debug-close" onclick="document.getElementById('esport-debug-bar').style.display='none'">
            ✕ Close
        </button>
    </div>
    
    <!-- Onglets -->
    <div class="tab-buttons">
        <div class="tab-button active" data-tab="queries">
            🗄️ Queries (<?= $queriesCount ?>)
        </div>
        <div class="tab-button" data-tab="security">
            🔒 Security (<?= count($this->securityChecks) ?>)
        </div>
        <div class="tab-button" data-tab="logs">
            📝 Logs (<?= count($this->logs) ?>)
        </div>
        <div class="tab-button" data-tab="files">
            🔧 Files (<?= $filesCount ?>)
        </div>
    </div>
    
    <!-- Contenu: Queries -->
    <div class="tab-content active" id="tab-queries">
        <?php if (empty($this->queries)): ?>
            <div style="color: #888;">Aucune requête SQL exécutée</div>
        <?php else: ?>
            <?php foreach ($this->queries as $i => $query): ?>
                <div class="query-item <?= $query['slow'] ? 'slow' : '' ?>">
                    <div class="query-meta">
                        <strong>#<?= $i + 1 ?></strong>
                        <span style="margin-left: 15px;">⏱️ <?= number_format($query['time'], 4) ?>s</span>
                        <?php if ($query['slow']): ?>
                            <span style="color: #ff9800; margin-left: 10px;">⚠️ SLOW QUERY</span>
                        <?php endif; ?>
                    </div>
                    <div class="query-sql">
                        <?= htmlspecialchars($query['query']) ?>
                    </div>
                    <?php if (!empty($query['params'])): ?>
                        <div class="query-params">
                            Params: <?= htmlspecialchars(json_encode($query['params'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Contenu: Security -->
    <div class="tab-content" id="tab-security">
        <?php if (empty($this->securityChecks)): ?>
            <div style="color: #888;">Aucun check de sécurité enregistré</div>
        <?php else: ?>
            <?php foreach ($this->securityChecks as $check): ?>
                <div class="security-check <?= $check['passed'] ? 'passed' : 'failed' ?>">
                    <div style="font-size: 20px;">
                        <?= $check['passed'] ? '✅' : '❌' ?>
                    </div>
                    <div style="flex: 1;">
                        <strong><?= htmlspecialchars($check['check']) ?></strong>
                        <?php if ($check['message']): ?>
                            <div style="color: #888; font-size: 11px; margin-top: 3px;">
                                <?= htmlspecialchars($check['message']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Contenu: Logs -->
    <div class="tab-content" id="tab-logs">
        <?php if (empty($this->logs)): ?>
            <div style="color: #888;">Aucun log enregistré</div>
        <?php else: ?>
            <?php foreach ($this->logs as $log): ?>
                <div class="log-item">
                    <span class="log-level <?= $log['level'] ?>"><?= $log['level'] ?></span>
                    <span><?= htmlspecialchars($log['message']) ?></span>
                    <?php if (!empty($log['context'])): ?>
                        <div style="color: #888; font-size: 11px; margin-top: 5px; margin-left: 70px;">
                            <?= htmlspecialchars(json_encode($log['context'], JSON_PRETTY_PRINT)) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Contenu: Files -->
    <div class="tab-content" id="tab-files">
        <?php foreach ($includedFiles as $file): ?>
            <div class="file-item">
                <?= htmlspecialchars(str_replace($_SERVER['DOCUMENT_ROOT'], '', $file)) ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
(function() {
    // Gestion onglets
    document.querySelectorAll('#esport-debug-bar .tab-button').forEach(button => {
        button.addEventListener('click', () => {
            // Désactiver tous
            document.querySelectorAll('#esport-debug-bar .tab-button').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('#esport-debug-bar .tab-content').forEach(c => c.classList.remove('active'));
            
            // Activer cliqué
            button.classList.add('active');
            document.getElementById('tab-' + button.dataset.tab).classList.add('active');
        });
    });
})();
</script>

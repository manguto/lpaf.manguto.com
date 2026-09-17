    </main>
    <footer>
        <div>
            <strong><?= e($app->setting('app_name', $app->config->get('app_name'))) ?></strong> &bull; LPAF v<?= e($app->setting('app_version', '0.1.0')) ?>
        </div>
        <div style="margin-top: 0.35rem; font-size: 0.775rem; color: #94a3b8;">
            Ambiente <?= e($app->config->get('app_env', 'local')) ?> &bull; Persistência CSV
        </div>
    </footer>
</body>
</html>
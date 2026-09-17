<h1>Diagnóstico</h1>
<section>
    <p>PHP: <?= e(PHP_VERSION) ?></p>
    <p>Ambiente: <?= e($app->config->get('app_env')) ?></p>
    <p>Aplicação: <?= e($app->setting('app_name', $app->config->get('app_name'))) ?></p>
    <p>Storage: <?= e($app->config->get('storage_data')) ?></p>
</section>
<table>
    <tr>
        <th>Arquivo</th>
        <th>Existe</th>
        <th>Registros</th>
    </tr><?php foreach ($status as $file => $info): ?><tr>
            <td><?= e($file) ?></td>
            <td><?= $info['exists'] ? 'Sim' : 'Não' ?></td>
            <td><?= e($info['rows']) ?></td>
        </tr><?php endforeach; ?>
</table>
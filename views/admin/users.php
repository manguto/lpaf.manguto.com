<h1>Usuários</h1>
<p><a class="button" href="<?= url($app, '/admin/users/create') ?>">Novo usuário</a></p>
<table>
    <tr>
        <th>Nome</th>
        <th>Login</th>
        <th>Status</th>
        <th>Ação</th>
    </tr><?php foreach ($users as $item): ?><tr>
            <td><?= e($item['name']) ?></td>
            <td><?= e($item['username']) ?></td>
            <td><?= $item['active'] === '1' ? 'Ativo' : 'Inativo' ?></td>
            <td><a href="<?= url($app, '/admin/users/' . $item['id'] . '/edit') ?>">Editar</a></td>
        </tr><?php endforeach; ?>
</table>
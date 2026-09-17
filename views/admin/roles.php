<h1>Perfis</h1>
<p><a class="button" href="/admin/roles/create">Novo perfil</a></p>
<table>
    <tr>
        <th>Nome</th>
        <th>Identificador</th>
        <th>Ação</th>
    </tr><?php foreach ($roles as $role): ?><tr>
            <td><?= e($role['name']) ?></td>
            <td><?= e($role['id']) ?></td>
            <td><a href="/admin/roles/<?= e($role['id']) ?>/edit">Editar</a></td>
        </tr><?php endforeach; ?>
</table>
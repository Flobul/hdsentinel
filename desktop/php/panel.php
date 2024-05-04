
<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
echo '<br/>';
include __DIR__ . '/../modal/health.php';
?>
<?php //netteCache[01]000176a:2:{s:4:"time";s:21:"0.03183800 1241391836";s:2:"df";a:1:{s:92:"E:\web-data\projects_svn\public\kvazar\document_root/../app/templates/Homepage/default.phtml";i:1241391833;}}?><?php
// template E:\web-data\projects_svn\public\kvazar\document_root/../app/templates/Homepage/default.phtml
?><?php $_cb = CurlyBracketsFilter::initState($template) ?><?php
if (SnippetHelper::$outputAllowed) {
?>
<h2><a href="<?php echo TemplateHelpers::escapeHtml($presenter->link('User:')) ?>">User:</a></h2>
<div class="block">
	<a href="<?php echo TemplateHelpers::escapeHtml($presenter->link('User:login')) ?>">Login</a>
	<a href="<?php echo TemplateHelpers::escapeHtml($presenter->link('User:registration')) ?>">Registration</a>
	<a href="<?php echo TemplateHelpers::escapeHtml($presenter->link('User:logout')) ?>">Logout</a>
</div>
<h2><a href="<?php echo TemplateHelpers::escapeHtml($presenter->link('Quiz:')) ?>">Quiz:</a></h2>
<?php if ($user->isAuthenticated() && $user->getIdentity()->id == 5): ?>
<h2><a href="<?php echo TemplateHelpers::escapeHtml($presenter->link('Question:')) ?>">Question:</a></h2>
<?php endif ?><?php
}
?>
<?php //netteCache[01]000168a:2:{s:4:"time";s:21:"0.18781100 1241489764";s:2:"df";a:1:{s:84:"E:\web-data\projects_svn\public\kvazar\document_root/../app/templates//@layout.phtml";i:1241489759;}}?><?php
// template E:\web-data\projects_svn\public\kvazar\document_root/../app/templates//@layout.phtml
?><?php $_cb = CurlyBracketsFilter::initState($template) ?><?php
if (SnippetHelper::$outputAllowed) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="Content-Language" content="en" />

	<meta name="description" content="Nette Framework web application skeleton" />
	<?php if (isset($robots)): ?><meta name="robots" content="<?php echo TemplateHelpers::escapeHtml($robots) ?>"><?php endif ?>


	<title><?php echo TemplateHelpers::escapeHtml($title) ?></title>

	<link rel="stylesheet" media="screen,projection,tv" href="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>css/screen.css" type="text/css" />
	<link rel="stylesheet" media="print" href="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>css/print.css" type="text/css" />
	<link rel="shortcut icon" href="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>favicon.ico" type="image/x-icon" />
<?php if ($user->isAuthenticated()): ?>
	<script type="text/javascript" src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>js/jquery.js"></script>
	<script type="text/javascript" src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>js/nette.js"></script>
	<script type="text/javascript" src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>js/functions.js"></script>
<?php endif ?>
</head>

<body>

	<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($flashes) as $flash): ?><div class="flash <?php echo TemplateHelpers::escapeHtml($flash->type) ?>"><?php echo TemplateHelpers::escapeHtml($flash->message) ?></div><?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>

<?php if ($presenter->name == "Homepage"): ?>
	<h1><a href="<?php echo TemplateHelpers::escapeHtml($presenter->link('Homepage:')) ?>">Homepage</a></h1>
<?php else: ?>
	<strong><a href="<?php echo TemplateHelpers::escapeHtml($presenter->link('Homepage:')) ?>">Homepage</a></strong>
<?php endif ?>
	<div id="content">

		<?php } ?><?php echo $template->subTemplate($content)->__toString(TRUE) ?><?php if (SnippetHelper::$outputAllowed) { ?>
		
<?php if ($user->isAuthenticated()): ?>
		<div id="sidebar" >
			<div id="user-info">
				<strong><?php echo TemplateHelpers::escapeHtml($user->getIdentity()->nick) ?></strong><br />
				<span><?php echo TemplateHelpers::escapeHtml($user->getIdentity()->email) ?></span>
			</div>
<?php endif ?>
		
		<?php } ?><?php echo TemplateHelpers::escapeHtml($logged_users->render()) ?><?php if (SnippetHelper::$outputAllowed) { ?>
		
<?php if ($user->isAuthenticated()): ?>
		</div>
<?php endif ?>
		

	</div>
</body>
</html>
<?php
}
?>
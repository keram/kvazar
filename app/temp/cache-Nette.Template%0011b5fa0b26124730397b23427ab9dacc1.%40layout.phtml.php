<?php //netteCache[01]000168a:2:{s:4:"time";s:21:"0.13546600 1241139275";s:2:"df";a:1:{s:84:"E:\web-data\projects_svn\public\kvazar\document_root/../app/templates//@layout.phtml";i:1241007476;}}?><?php
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
</head>

<body>
	<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($flashes) as $flash): ?><div class="flash <?php echo TemplateHelpers::escapeHtml($flash->type) ?>"><?php echo TemplateHelpers::escapeHtml($flash->message) ?></div><?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>


<?php echo $template->subTemplate($content)->__toString(TRUE) ?>
</body>
</html>
<?php
}
?>
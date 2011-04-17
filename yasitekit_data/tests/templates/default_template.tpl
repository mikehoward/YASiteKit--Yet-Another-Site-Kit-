{:meta generator TextMake http://macromates.com/:}
{:meta author Mike:}
{:css /css/screen.css screen:}
{:css /css/print.css print:}
{:css /css/handheld.css handheld:}
{:javascript /javascript/jquery-1.4.2.js:}
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>{: $page_title :}</title>
	{:render meta:}
	{:render css:}
</head>
<body>
{: include header.tpl :}
{: include account_nav.tpl :}

<div id="content-container">
  <div id="content" class="box">
{: yatemplate-content :}    
  </div> <!-- content -->

  <div id="secondary-menu">
    {: include secondary_nav.php :}
  </div>
</div> <!-- end content-container -- >

{: include main_nav.tpl :}
{: include footer.tpl :}
{: render javascript :}
</body>
</html>

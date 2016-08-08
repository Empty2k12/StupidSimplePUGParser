# StupidSimplePUGParser Test


This parser will convert

```
html(lang="en")
  head
    title PUG parsed by PHP
  body
    h1 Templating has never been easier!
    div.highlight
      p(style="color: white;") Just drop StupidSimplePUGParser.php in your project,
      div#number1
        p include it and get parsin!
        br
        a(href="https://google.com/") YES, IT'S THAT EASY!
```

to

```
<html lang="en">
	<head>
		<title>PUG parsed by PHP</title>
	</head>
	<body>
		<h1>Templating has never been easier!</h1>
		<div class="highlight">
			<p style="color: white;">Just drop StupidSimplePUGParser.php in your project,</p>
			<div id="number1">
				<p>include it and get parsin!</p>
				<br/>
				<a href="https://google.com/">YES, IT'S THAT EASY!</a>
			</div>
		</div>
	</body>
</html>
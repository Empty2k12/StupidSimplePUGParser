# StupidSimplePUGParser

StupidSimplePUGParser is a PHP class which parses and converts .pug files to HTML

**It is in no way feature complete and should only be used in situations where a full fledged parser such as [php-pug](https://github.com/pug-php/pug) is not necessary!**

This project is inspired by kafene's [microhaml](https://github.com/kafene/microhaml)

## Features
* ✔ Classes, IDs, Attributes
* ✔ Include File (`include filename.pug`, includes can include more files)
* ✔ Pipe Operator (`|` :arrow_right: `p`)
* ✔ Self Closing Tags (`selfclosing/` and HTML Selfclosing Tags)
* ✔ Variables (`#{pageTitle}` => `My Title`)
* ✔ Cache
* ✔ Doctype Declarations (Custom and PUG presets)
* ✔ Blocking (`//-`) and non-blocking (`//`) comments
* ✔ Correct HTML formatting
* ✘ Any other language features from the [PUG Deocumentation](http://jade-lang.com/reference/)

## Usage
```
echo StupidSimplePugParser::create()
    ->withFile("views/home/index.pug") //Reads in File
    ->withCode("h1 Header 1")          //Uses PUG Code
    ->setOptions($options)             //Sets options (OPTIONAL)
    ->toHtml();                        //Parses and outputs html code
```

Sample Usage
```
echo StupidSimplePugParser::create()
    ->withFile("views/home/index.pug")
    ->setOptions(array(
        "filesIndentedBy" => 4,
        "cache" => true,
        "variables" => array(
            "pageTitle" => "My Title",
            "pageText" => "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam..."
        )
    ))
    ->toHtml();
```

All Options
```
$options = array(
    //Additional Indentation (Default: 0, this is required internally for includes)
    "additionalIndent" => 2,

    //Intendation of the PUG files (Usually 2 or 4, default: 2)
    "filesIndentedBy" => 4,

    //Enables Cache
    "cache" => true,

    //Sets custom cache directory (Default: pug_cache/)
    "cacheDir" => "my_custom_cache_directory/",

    //Variables: They are defined as #{title} in the PUG code, they output !!{title} if not found
    "variables" => array(
        "title" => "Appentdecker"
    )
);
```

## Planned Features
* Implement more features from the [PUG Documentation](http://jade-lang.com/reference/)
* Proper Benchmarking and Speed Improvements
* Extends (Template Inheritance)

## Contributing
1. Fork it!
2. Create your feature branch: `git checkout -b my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin my-new-feature`
5. Submit a pull request :D

## History
For a detailed overview over changes to this Repository check the Master commit tree [here](https://github.com/Empty2k12/TXWatch/commits/master)

## Notes
Beware of ugly code.

Bugfixes are generally welcome. If you want to contribute something which changes major parts of the parser, please talk to someone with commit privileges first. Nothing is more frustrating than putting a lot of work and effort into a new feature and then having the PR rejected because it doesn’t fit design-wise.

**The philosophy of this project is keeping the parser in a single class, please respect that when contributing!**

If you submit a PR you must accept the Contributor License Agreement. There is no way around that, since otherwise changing the license later - even to something more permissive! - , becomes close to impossible.

## License

**The MIT License (MIT)**

Copyright (c) 2016 Gero Gerke

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
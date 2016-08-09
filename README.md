# StupidSimplePUGParser

StupidSimplePUGParser is a PHP class which parses and converts .pug files to HTML

**It is in no way feature complete and should only be used in situations where a full fledged parser such as [php-pug](https://github.com/pug-php/pug) is not necessary!**

This project is inspired by kafene's [microhaml](https://github.com/kafene/microhaml)

## Features
* ✔ Classes, IDs, Attributes
* ✔ Include File (`include filename.pug`)
* ✔ Pipe Operator (`|` :arrow_right: `p`)
* ✔ Self Closing Tags
* ✔ Doctype Declarations (Custom and PUG presets)
* ✔ Blocking (`//-`) and non-blocking (`//`) comments
* ✔ Correct HTML formatting
* ✘ Any other language features from the [PUG Deocumentation](http://jade-lang.com/reference/)

## Planned Features
* Implement more features from the [PUG Deocumentation](http://jade-lang.com/reference/)
* Optimize Speed
* Extends (Template Inheritance)
* Include (Template Inclusion)

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
**The philosophy of this project is keeping the parser in a single class, please repect that when contributing!**

If you submit a PR you must accept the Contributor License Agreement. There is no way around that, since otherwise changing the license later - even to something more permissive! - , becomes close to impossible.

## License

**The MIT License (MIT)**

Copyright (c) 2016 Gero Gerke

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
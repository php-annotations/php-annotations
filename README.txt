php-annotations
===============

Source-code annotations for PHP.

Copyright (C) 2011-2012 Rasmus Schultz <rasmus@mindplay.dk>

https://github.com/mindplay-dk/php-annotations

For documentation and updates, please visit the project Wiki:

https://github.com/mindplay-dk/php-annotations/wiki


Project Structure
-----------------

The files in this project are organized as follows:

  php-annotations         This README and the LGPL license
    /Annotation           The core of the library itself
      /Standard           Standard library of annotation classes
    /demo                 Browser-based example/demonstration
    /test                 Unit tests for the core of the library
      /test.php           Browser-based test suite runner
      /lib                Unit test library
      /runtime            Run-time cache folder used for tests
      /suite              The test suite for the unit test framework

The "Annotation" folder is the only folder required for the annotation
framework itself - other folders contain demonstration code, tests, etc.

To run the test suite, run "php-annotations/test/test.php" from a
browser - a summary of the test-results will be displayed on the page.


Code Style
----------

Largely PSR-2 compliant:

https://raw.github.com/php-fig/fig-standards/master/accepted/PSR-2-coding-style-guide.md


License
-------

http://www.gnu.org/licenses/lgpl-3.0.txt

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License as
published by the Free Software Foundation; either version 3 of
the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses>.

Additional permission under GNU GPL version 3 section 7

If you modify this Program, or any covered work, by linking or
combining it with php-annotations (or a modified version of that
library), containing parts covered by the terms of the LGPL, the
licensors of this Program grant you additional permission to convey
the resulting work.

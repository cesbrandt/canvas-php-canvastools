# CanvasTools

## Table of Contents
- [Introduction](#introduction)
- [Highlights](#highlights)
- [Requirements](#requirements)
- [Install](#install)
- [How To Use](#how-to-use)
- [Creating a New Report](#creating-a-new-report)
  - [class.Template.php](#classtemplatephp)
  - [Report Generation](#report-generation)
  - [Row URLs](#row-urls)
- [License](#license)
- [Additional Applicable License](#additional-applicable-license)

## Introduction
CanvasTools is a robust report generator for the [Canvas LMS](https://github.com/instructure/canvas-lms) developed by Instructure. Its development and distribution reflects no relationship between with Instructure and is no way officially supported by Instructure.

CanvasTools was developed as an "easy" means to build complex reports, complexity being directly related to the API query requirements to generate said report, without having to give any focus to the querying. It was designed to be expandable and allows for GET, PUT, POST, and DELETE calls.

Queries for all all supplied calls have been tested and verified with the Canvas API, as of 2016-07-26. Additional tests have been ran to confirm that PUT, POST, and DELETE calls function, though none are included. Though it was designed with the ability to execute modification queries, it is not intended to. CanvasTools is intended to be used for report generation, nothing more. Any use of it beyond the supplied GET functions will is not supported and the implementers take full responsibility for using it such.

## Highlights
- Uniform API calling
- Expansive call structure

## Requirements
You need:
- PHP >= 5.2.0
- PHP extensions:
  - php_zip
  - php_xml
  - php_gd2
- HTTPS
  - **Note**: It is possible to use without HTTPS if you disable `CURLOPT_SSL_VERIFYHOST` and `CURLOPT_SSL_VERIFYPEER`, though this is discouraged.
- Canvas LMS Authorization Token (Account 1 Admin)
- HTML report generator
- Excel report exporting

## Install
There is nothing special about CanvasTools. So long as the requirements are met, it's a simple matter of putting the files on the webserver and configuring `config.php`.

**Note**: There are no controls to restrict access to CanvasTools. It is **recommended** that where it is placed is a secure server with access controls to prevent unwarranted access.

## How To Use
CanvasTools was designed with the intent of making all reports a self-contained PHP class. These classes are stored in `./classes` and named for the class contained within (i.e., `class AccountTree {}` is stored in the file `class.AccountTree.php`). **Note**: CanvasTools was created with simplicity as a key goal. The name of the `class` **MUST** match the name in the filename.

There are five (5) "reports" included with CanvasTools:
- Account Tree
- Content Search
- File Search
- LTI Locator
- Course Dates

Reports are enabled in the `config.php` file. Simply add/remove the name of the classes whose reports you want enabled.

As demonstrated by the `AccountTree` class, "report" is loosely used. The primary goal is a simplified API querying of the Canvas LMS. However, a report template has been created from generated meaningful reports from the data retrieved. These generic "reports" can be modified to supply any information pulled from the API calls.

## Creating a New Report

###### class.Template.php
There is a `class.Template.php` file with the bare minimum function structure needed for a report to be integrated with CanvasTools.

The `config()` class provides the class title and description to CanvasTools. These are displayed on the navigational bar and homepage, respectively.

`generatePage()` is the function called whenever that report class is accessed. In this class, you will need to build your structure for handling customization settings and setting submission/report generation.

Expanding the class to accommodate the full functionality of the report handling is recommended, but remember that everything must be initially triggered from the `generatePage` function. The key to making new reports is to build out the logic needed to create the report. CanvasTools simplifies the API calls and offers a uniform report generation, but it does not expand upon and simplify the API in any manner.

###### Report Generation
To configure the report, you need to build a multi-dimensional associative array with the following information:
- 'title' => string **(Optional)**
- 'heading' => string **(Optional)**
- 'columns' => iterative array
  - associative array for each column containing:
    - 'title' => string
    - 'name' => string
      - **Note**: These correspond to the names fo the data entered into the `results`.
    - 'class' => string **(Optional)**
      - **Note**: The only supported class is `center`, which will center the content in both report formats.
- 'results' => iterative array
  - associative array for each row of data
```
$data = array(
  'title' => 'Demo Report',
  'heading' => 'This is like a subheader, or description, field.',
  'columns' => array(
    array(
      'title' => 'ID',
      'name' => 'id',
      'class' => 'center'
    ),
    array(
      'title' => 'Name',
      'name' => 'name'
    ),
    array(
      'title' => 'Valid?',
      'name' => 'valid',
      'class' => 'center'
    )
  ),
  'results' => array(
    array(
      'id' => 1,
      'name' => 'ABC123',
      'valid' => 'Yes'
    ),
    array(
      'id' => 2,
      'name' => 'DEF456',
      'valid' => 'No'
    ),
    array(
      'id' => 3,
      'name' => 'GHI789',
      'valid' => 'Yes'
    ),
    array(
      'id' => 4,
      'name' => 'JKL012',
      'valid' => 'No'
    ),
    array(
      'id' => 5,
      'name' => 'MNO345',
      'valid' => 'No'
    )
  )
);
```
How this array is generated is entirely upto you, but it **MUST** follow that structure.

Once the array has been built simply run it through the `Report` class:
```
return Report::HTML($data);
```
The above example would result in a report like:
```
  ID    Name    Valid?
  1    ABC123    Yes
  2    DEF456     No
  3    GHI789    Yes
  4    JKL012     No
  5    MNO345     No
```
###### Row URLs
There is only one reserved column title: url

This title is used to identify a URL to be associated with that row of data. It could be the URL to a listed activity, course, account, etc. It is also an optional column. If no `url` entry is provided, then the row will not be turned into a pseudo-anchor (JavaScript/CSS will emulate normal anchor behavior).

Having said that, you can have reports with mixed results. Some will have URLs while others will not. Therefore, the column must **NOT** be declared in the array as a column. It will be automatically added as the last column in the report if a single URL is present.

Associating a URL with a data entry is as simple as adding it to the the array for that entry:
```
  'results' => array(
    array(
      'id' => 1,
      'name' => 'No URL'
    ),
    array(
      'id' => 2,
      'name' => 'URL',
      'url' => 'https://this.is.the/url'
    )
  )
```
This example would have a regular entry for the first row and a pseudo-anchor for the second.

## License
CanvasTools falls under The MIT License (MIT). Please see the [LICENSE](https://github.com/cesbrandt/canvas-php-canvastools/blob/master/LICENSE) file for the license agreement.

## Additional Applicable License
PHPExcel ([commit fcc5c6585574054bd2dce530d5fb3f5da745bc49](https://github.com/PHPOffice/PHPExcel/commit/fcc5c6585574054bd2dce530d5fb3f5da745bc49)) is utilized with minor modification to integrate with CanvasTools. The version was retrieved and modified on 2016-07-06 from https://github.com/PHPOffice/PHPExcel. The license holders of PHPExcel retain full rights in accordance with the GNU Lesser General Public License.

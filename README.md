Laravel CSV translation (a.k.a LCT) offers a different approach to translate strings in Laravel 6. Translations are stored in safe, non-executable CSV files so you can edit them easily with any spreadsheet editor.

**Please note:** this concept is experimental. Feel free to open issues and submit your PRs!

## Why ##
By default, Laravel provides two ways of storing translations - PHP arrays and JSON strings.

**Disadvantages of PHP arrays:**

Executable code requires translators to properly escape characters, use valid PHP syntax, otherwise you get a fatal error. Message IDs could be a nightmare when you develop large applications.

**Disadvantages of JSON**

Although JSON could be considered as a safe format, translators still have to properly escape characters and deal with the non-human-friendly format. No message IDs, but now you cannot divide your translations, which means all the strings (thousands and thousands for large apps) are loaded in memory.

## Introducing LCT ##


- Safe, human-readable format
- Handy. Use regular spreadsheet programs to edit translations
- DRY. No message IDs
- Optimized. Automatically splits translations by "context" files
- Smart. Finds and adds untranslated strings to original files on the fly


## Usage ##

LCT provides the following functions (and corresponding Blade directives):

Translate regular strings:
    
    t(string $key, array $replace = [], string $context = null)

Translate plural strings:
    
    tp(string $key, $number, array $replace = [], string $context = null)

Set global translation contex:

    trans_context(string $context = null)

## How it works ##

Let's see what happens when translating `Hello :user` into ukrainian language

	app()->setLocale('uk'); // Set ukrainian language globally
    t('Hello :user', ['user' => 'Юра'], 'user');

First, LCT will try to find and parse a CSV file for the current context. We have specified "user" so the expected file is `resources/lang/uk/context/user.csv`.

Once found and parsed, LCT checks `Hello :user` key in the array of translations. If the key is set, further processing is stopped and we see the translated string.

Otherwise, LCT will check "parent" file `resources/lang/uk/context/_uk.csv`, which is a working copy of `resources/lang/uk/_uk.csv` (has been copied automatically during translator initialization)

If the parent file doesn't contain `Hello :user`, LCT loads the distributed translation file `resources/lang/uk/uk.csv` (contains all translations for your app) and appends `Hello :user` key to `resources/lang/uk/context/user.csv` and `resources/lang/uk/context/_uk.csv` so you can translate it later using a spreadsheet editor.

*As you see, specifying context in `t()` function can be quite tedious. You can skip it and use default route URI context or set globally (e.g per view)*

**Route URI context**

This is by default. Say we're at page "product/some-id" which is result of this route definition:

    Route::get('product/{id}', function () {
      return view('product');
    });

The expected context file for this route will be `resources/lang/uk/context/uri-product-id.csv`.

**Setting context globally**

Use `trans_context()` in regular PHP files and `@trans_context` in Blade views

    <?php
    
    trans_context('user');
    
    echo t('Hello :user', ['user' => 'Юра']); // Uses "user" context
    
    trans_context(); // Reset context to default
    
    echo t('Hello :user', ['user' => 'Юра']); // Uses URI context

Blade views:

    @trans_context('user');
    
    {{ t('Hello :user', ['user' => 'Юра']) }} <!-- Uses "user" context -->
    
    @trans_context; <!-- Reset context to default -->
    
    {{ @t('Hello :user', ['user' => 'Юра']) }} <!-- Uses URI context -->


**Pluralization**

Use `tp()` in regular PHP files and `@tp()` in Blade views. They are equal to Laravel's [trans_choice()](https://laravel.com/docs/6.x/localization#pluralization)

## Installation ##

    composer require sribna/laravel-csv-translation









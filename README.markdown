Frontend Localisation
==============

Offers a frontend localisation mechanism using XML files.

* Version: 0.1 beta
* Build Date: 2011-10-24
* Authors:
	- [Xander Group](http://www.xandergroup.ro)
	- Vlad Ghita
* Requirements:
	- Symphony 2.2 or above
	- At least one frontend language driver. See **Adding a Language Driver** in **4.1 Frontend Language** section.

Thank you all other Symphony & Extensions developers for your inspirational work.



## 1 Synopsis ##

Frontend localisation in Symphony (and other systems) implies coverage of two problems:<br />
1. Frontend language detection and (optional) redirect mechanism.<br />
2. Translation mechanism of static text, whether it's a few words long or a few paragraphs.<br />

For problem 1, there are a few extensions that provide this functionality, but there is a lack of unified approach. This extension is decoupled from any of these drivers and provides a mechanism to associate it with them. More details in **4.1 Frontend Language** section.<br />
For problem 2, this extension offers a translation mechanism using XML files. More details in **4.2 Translation Manager** section.



## 2 Features ##
For site builders:

* allows association of Pages to Translation files and vice-versa.
* @todo allows editing of Translation Files in Admin
* changeble `Language Driver`
* changeble `Reference Language`
* @todo changeble `Translation Path`
* changeable `Page name prefix` to distinguish Pages' Translation Files from other Translation Files.
* Translations consolidation on unsinstall
* one button update of all language Translations referencing `Reference Language`
* offers a Datasource with strings from all Translation Files attached to current page. 

For PHP developers:

* unifies Frontend language drivers in one access point.



## 3 Installation ##

1. Make sure you have at least one language driver installed. [Language Redirect](https://github.com/klaftertief/language_redirect), for example.
1. Upload the `frontend_localisation` folder found in this archive to your Symphony `extensions` folder.    
2. Enable it by selecting `Frontend Localisation` under `System -> Extensions`, choose Enable from the with-selected menu, then click Apply.



## 4 Usage ##

### 4.1 Frontend Language ###

#### 4.1.1 @ PHP developers ####

This extension provides a [FrontendLanguage class](https://github.com/vlad-ghita/frontend_localisation/blob/master/lib/class.FrontendLanguage.php) implementing [Singleton interface](https://github.com/symphonycms/symphony-2/blob/master/symphony/lib/core/interface.singleton.php) for easy access to Frontend language information.
For default Language Driver, you must install extension [Language Redirect](https://github.com/klaftertief/language_redirect) by Jonas Coch, at least version 1.0.2.

##### Adding a Language Driver #####

1. In [$supported_language_drivers](https://github.com/vlad-ghita/frontend_localisation/blob/master/lib/class.FrontendLanguage.php#L20) array add a name for this language driver with `value` set to extension folder of the driver.
2. Create a new class named `LanguageDriver<driver_name>` that extends `LangaugeDriver` abstract class. Save it in file `/frontend_localisation/lib/class.LanguageDriver<driver_name>.php`. You must implement all abstract methods from [LangaugeDriver class](https://github.com/vlad-ghita/frontend_localisation/blob/master/lib/class.LanguageDriver.php)
3. Done. You can now select this driver on Preferences page.


#### 4.1.2 @ Site builders ####

On Preferences page you can select:

- `Language Driver` you want to use from supported and integrated ones. The Language Driver provides Frontend language information for the system.
- `Reference Language` is the language code which Translations will be used as reference when updating other languages Translations.


### 4.2 Translation Manager ###

#### 4.2.1 Preferences page ####

- `Translation Path` is the path in your `/workspace` directory where translation files will be stored.
- `Page Prefix` will be added at begin of translation filename to differentiate Symphony Page translation files from other translation files.
- `Consolidate Translations` is set default to `checked`. When this is checked, on uninstall, translation folders will **not** be deleted.
- pushing `Update Translation Files` button will update all languages Translations with reference to `Reference Language`. ***If a translation file is not marked as translated (`/data/meta/translated` is anything but `yes`), its contents will be changed to its reference file contents***.


#### 4.2.2 Managing Translations ####

Translation File's XML structure:

    <?xml version="1.0" encoding="UTF-8"?>
    <data>
        <meta>
            <translated>yes / no</translated>
            <language code="__language-code__" handle="__language-handle__">__language-name__</language>
        </meta>
        <!--Business logic nodes here-->
        <item handle="" />
    </data>

`/data/meta/translated` - marks this file as translated in this `/data/meta/language`. If this is set to `yes`, on updating from Preferences Page, this file will be skipped.

`data` and `meta` node are mandatory. At some extent, the extension [ensures this structure](https://github.com/vlad-ghita/frontend_localisation/blob/master/lib/class.TranslationFile.php#L152-187) to keep the file usable. **As a rule of thumb, keep these nodes as they are, change only `/data/meta/translated`**.

Business information must be added as XML child elements of `data` node. This is **your** responsibility. It's your choice how to build your data. See **4.2.3** for an example.

**Adding Translations to Pages**

1. Create / Edit a page.
2. Add Translations the same way you add Events and Datasources.
3. Add Datasource `Frontend Localisation` to your page.
4. Inspect XML output in debug -> `/data/frontend-localisation`.

#### 4.2.3 An example ####

If Frontend language is english and in `/translations/en` I have:

`general.xml`:

    <?xml version="1.0" encoding="UTF-8"?>
    <data>
        <meta>
            <translated>yes</translated>
            <language code="en" handle="english">English</language>
        </meta>
        <!--Business logic nodes here-->
        <item handle="page-not-found">Page not found</item>
        <item handle="you-are-here">You are here.</item>
        <item handle="image">Image</item>
        <fancy-stuff>
            <item handle="irrelevat">Please not that your request is ireelevat.</item>
            <item handle="just-a-handle">Yet another Frontend Localisation Extension.</item>
        </fancy-stuff>
    </data>

`pagina_contact.xml`:

    <?xml version="1.0" encoding="UTF-8"?>
    <data>
        <meta>
            <translated>yes</translated>
            <language code="en" handle="english">English</language>
        </meta>
        <!--Business logic nodes here-->
        <item handle="name">Name :</item>
        <item handle="address">Contact address</item>
        <item handle="funky-handle">Telephone</item>
        <item handle="mandatory-name">
            <p>
                Name is a mandatory field.
            </p>
        </item>
        <item handle="message-success">
            <p>
                Message sent successfully. Return <a href="link_home">Home</a> to continue. Well, you <span style="color:red">get the point</span>.
            </p>
        </item>
    </data>

If I attach these Translations to a page, XML output will look like this:

    <data>
        ...
        <frontend-localisation>
            <!-- these are from general.xml -->
            <item handle="page-not-found">Page not found</item>
            <item handle="you-are-here">You are here.</item>
            <item handle="image">Image</item>
            <fancy-stuff>
                <item handle="irrelevat">Please not that your request is ireelevat.</item>
                <item handle="just-a-handle">Yet another Frontend Localisation Extension.</item>
            </fancy-stuff>

            <!-- these are from pagina_contact.xml -->
            <item handle="name">Name :</item>
            <item handle="address">Contact address</item>
            <item handle="funky-handle">Telephone</item>
            <item handle="mandatory-name">
                <p>
                    Name is a mandatory field.
                    </p>
            </item>
            <item handle="message-success">
                <p>
                    Message sent successfully. Return <a href="link_home">Home</a> to continue. Well, you <span style="color:red">get the point</span>.
                </p>
            </item>
        </frontend-localisation>
        ...
    </data>

##### Getting a value is trivial. #####

    <xsl:value-of select="/data/frontend-localisation/fancy-stuff/item[ @handle='just-a-handle' ]" />

will output

    Yet another Frontend Localisation Extension.



##### Easy access #####

In `master.xml` I like to add this:

    <xsl:variable name="__">
        <xsl:copy-of select="/data/frontend-localisation" />
    </xsl:variable>

or all `item` nodes if their handle is unique (so no conflicts appear):

    <xsl:variable name="__">
        <xsl:copy-of select="/data/frontend-localisation//item" />
    </xsl:variable>

Now use `$__/item[ @handle='xxxxxx' ]` to output what you need.

##### Advanced stuff #####

You have a link in your `item`? [Ninja technique](http://symphony-cms.com/learn/articles/view/html-ninja-technique/) suites you perfect:

    <xsl:apply-templates select="$__/item[ @handle='message-success' ]" mode="links" />

    <xsl:template match="*">
        <xsl:element name="{name()}">
            <xsl:apply-templates select="* | @* | text()" mode="links" />
        </xsl:element>
    </xsl:template>

    <xsl:template match="@*">
        <xsl:attribute name="{name(.)}">
            <xsl:value-of select="."/>
        </xsl:attribute>
    </xsl:template>

    <xsl:template match="a">
        <xsl:attribute name="a">
            <xsl:choose>
                <xsl:when test=". = 'link_home'">
                    __generate href to home here__
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="."/>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:attribute>
    </xsl:template>



## 5 Compatibility ##

         Symphony | Frontend Localisation
------------------|----------------
      2.0 — 2.1.* | Not compatible
      2.2 - *     | [latest](https://github.com/vlad-ghita/frontend_localisation)

Language Redirect | Frontend Localisation
------------------|----------------
        * — 1.0.1 | Not compatible
    1.0.2 - *     | [latest](https://github.com/vlad-ghita/frontend_localisation)



## 6 Changelog ##

- 0.1beta, 24 October 2011
    - initial beta release.

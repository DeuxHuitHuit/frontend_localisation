<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
		xmlns:exsl="http://exslt.org/common"
		xmlns:fl="http://symphony-cms.com/functions"
		xmlns:func="http://exslt.org/functions"
		xmlns:str="http://exslt.org/strings"
		extension-element-prefixes="exsl func str">


	<!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		#Translation utilities

		author:   Vlad Ghita
		email:    vlad_micutul@yahoo.com


		To use these functions add the "fl" namespace to your master (or wherever you feel
		it suits best) stylesheet and import this utility. Add the namespace to all stylesheets
		where you want to use these functions.


		=== Usage ===

				<xsl:stylesheet version="1.0"
					xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
					xmlns:fl="http://symphony-cms.com/functions"
					extension-element-prefixes="fl">

					<xsl:import href="fl_utilities.xsl"/>
					...
				</xsl:stylesheet>


		=== Instant grab™ a Translation ===

				<xsl:value-of select="fl:__([XPath]/[@handle])"/>

				[XPath] = the XPath from "/data/fl-translations" to "item"
				[@handle] = handle of desired item


		=== Examples ===

		Given this XML result (?debug):

				<fl-translations>

					// This comes from my data.p_page-404.xml file
					<p_page-404>
						<item handle="title">Page not found</item>
						<item handle="message"><![CDATA[<p>Requested page was not found. Return %1$s to continue.</p>]]></item>
					</p_page-404>

					// This comes from my data.p_news.xml file
					<p_news>
						<item handle="view-all"><![CDATA[View %1$s or head back to __PLACEHOLDER__.]]></item>
						<item handle="news-link"><![CDATA[all news]]></item>
						<item handle="home-link"><![CDATA[our spify home page]]></item>
					</p_news>

					// This comes from my data.template-master.xml file
					<item handle="site-name">Xander Advertising</item>

				</fl-translations>


		// Take a guess what these do:

				<xsl:copy-of select="fl:__('p_page-404/title')"/>
				<xsl:copy-of select="fl:__('site-name')"/>
				<xsl:copy-of select="fl:__('p_page-404/message')"/> // disable-output-escaping="yes"
				<xsl:value-of select="fl:__('p_page-404/message')"/>// disable-output-escaping="no"


		// Replace params

				<xsl:call-template name="fl__">
					<xsl:with-param name="context" select="'p_news/view-all'"/>
					<xsl:with-param name="reps">
						<rep><xsl:value-of select="fl:__('p_news/news-link')"/></rep>
						<rep loc="__PLACEHOLDER__"><xsl:value-of select="fl:__('p_news/home-link')"/></rep>
					</xsl:with-param>
				</xsl:call-template>


		// Replace params slick:

				<xsl:call-template name="fl__">
					<xsl:with-param name="context" select="'p_news/view-all'"/>
					<xsl:with-param name="reps">
						<rep>
							<a href="http://www.xanderadvertising.com" title="Xander Advertising">
								<span style="color:red">
									<xsl:value-of select="fl:__('p_news/news-link')"/>
								</span>
							</a>
						</rep>
						<rep loc="__PLACEHOLDER__">
							<a href="{/data/params/root}">
								<xsl:value-of select="fl:__('p_news/home-link')"/>
							</a>
						</rep>
					</xsl:with-param>
				</xsl:call-template>

		~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->

	<xsl:variable name="fl_translations" select="/data/fl-translations"/>




	<!--
		Instant grab™ a Translation

		<xsl:value-of select="fl:__('site-name')"/>
	-->
	<func:function name="fl:__">
		<!-- 
			It's made of 2 parts: [XPath]/[@handle]
			
			[XPath] = the XPath from "/data/fl-translations" to "item"
			[@handle] = handle of desired item
		 -->
		<xsl:param name="context" select="''"/>

		<!--
			Disable output escaping or not.
			== 'yes', HTML tags will be returned
			!= 'yes', encoded HTML tags will be returned
		-->
		<xsl:param name="disable-output-escaping" select="'yes'"/>

		<!--
			Translations pool
		-->
		<xsl:param name="translations" select="$fl_translations"/>


		<xsl:variable name="translation">
			<xsl:apply-templates select="exsl:node-set($translations)" mode="fl_translate">
				<xsl:with-param name="context" select="$context"/>
			</xsl:apply-templates>
		</xsl:variable>

		<func:result>
			<xsl:choose>
				<xsl:when test="$disable-output-escaping = 'yes'">
					<xsl:value-of select="$translation" disable-output-escaping="yes"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$translation"/>
				</xsl:otherwise>
			</xsl:choose>
		</func:result>
	</func:function>


	<xsl:template match="*" mode="fl_translate">
		<xsl:param name="context"/>

		<xsl:choose>
			<xsl:when test="contains($context, '/')">
				<xsl:apply-templates select="./*[name() = substring-before($context, '/')]" mode="fl_translate">
					<xsl:with-param name="context" select="substring-after($context, '/')"/>
				</xsl:apply-templates>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="./item[ @handle = $context ]"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>




	<!--
		XSL template used to replace variable placeholders.

		@var context - the-path/to-item. @see fl:__()
		@var reps - an XML nodeset with replacements to make.
		@var disable-output-escaping - ... duhh

		Structure of a rep:

		<rep loc="">HTML | plain text</rep>

		@attr loc = optional. It must be a placeholder. eg: %1$s, %2$s ... %n$s
		If @loc is ommited, it's built using the position of the `rep` node in `reps` parameter.

		___________________________
		eg:

		Translation file:

				<data>
					<p_news>
						<item handle="view-all"><![CDATA[View %1$s or head back to %2$s.]]></item>
						<item handle="news-link"><![CDATA[all news]]></item>
						<item handle="home-link"><![CDATA[our spify home page]]></item>
					</p_news>
				</data>

		Call template:

				<xsl:call-template name="fl__">
					<xsl:with-param name="context" select="'p_news/view-all'"/>
					<xsl:with-param name="reps">
						<rep loc="%1$s"><xsl:value-of select="fl:__('p_news/news-link')"/></rep>
						<rep><xsl:value-of select="fl:__('p_news/home-link')"/></rep>
					</xsl:with-param>
				</xsl:call-template>


		If your are slick, you can pass HTML code as well:

				<xsl:call-template name="fl__">
					<xsl:with-param name="context" select="'p_news/view-all'"/>
					<xsl:with-param name="reps">
						<rep>
							<a href="http://www.xanderadvertising.com" title="Xander Advertising">
								<span style="color:red">
									<xsl:value-of select="fl:__('p_news/news-link')"/>
								</span>
							</a>
						</rep>
						<rep>
							<a href="{/data/params/root}">
								<xsl:value-of select="fl:__('p_news/home-link')"/>
							</a>
						</rep>
					</xsl:with-param>
				</xsl:call-template>
	-->
	<xsl:template name="fl__">
		<xsl:param name="context"/>
		<xsl:param name="reps"/>
		<xsl:param name="disable-output-escaping" select="'yes'"/>
		<xsl:param name="translations" select="$fl_translations"/>


		<xsl:variable name="translation">
			<xsl:call-template name="fl_replace-var">
				<xsl:with-param name="haystack" select="fl:__($context, $disable-output-escaping, $translations)"/>
				<xsl:with-param name="reps" select="$reps"/>
				<xsl:with-param name="idx" select="1"/>
			</xsl:call-template>
		</xsl:variable>

		<xsl:choose>
			<xsl:when test="$disable-output-escaping = 'yes'">
				<xsl:variable name="output-escaped">
					<xsl:value-of select="$translation" disable-output-escaping="yes"/>
				</xsl:variable>

				<xsl:copy-of select="$output-escaped"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$translation"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<!--
		Replaces a variable.
	-->
	<xsl:template name="fl_replace-var">
		<xsl:param name="haystack"/>
		<xsl:param name="reps"/>
		<xsl:param name="idx"/>

		<xsl:choose>

			<!-- Return haystack if we reached the end -->
			<xsl:when test="$idx > count(exsl:node-set($reps)/rep)">
				<xsl:copy-of select="$haystack"/>
			</xsl:when>

			<!-- Replace current rep -->
			<xsl:otherwise>
				<xsl:variable name="rep" select="exsl:node-set($reps)/rep[position() = $idx]"/>

				<xsl:variable name="needle">
					<xsl:choose>
						<xsl:when test="$rep/@loc">
							<xsl:value-of select="$rep/@loc"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="concat('%',$idx,'$s')"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>

				<xsl:variable name="encoded_rep" select="fl:to-string($rep)"/>

				<xsl:call-template name="fl_replace-var">
					<xsl:with-param name="haystack" select="str:replace($haystack, $needle, $encoded_rep)"/>
					<xsl:with-param name="reps" select="$reps"/>
					<xsl:with-param name="idx" select="$idx + 1"/>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<!--
		Converts an HTML node to raw string.
	-->
	<func:function name="fl:to-string">
		<xsl:param name="node"/>

		<func:result>
			<xsl:choose>
				<!-- Special case for text-only node -->
				<xsl:when test="count(exsl:node-set($node)/*) = 0">
					<xsl:value-of select="$node"/>
				</xsl:when>

				<!-- Process the node -->
				<xsl:otherwise>
					<xsl:apply-templates select="exsl:node-set($node)/*" mode="fl_to-string"/>
				</xsl:otherwise>
			</xsl:choose>
		</func:result>
	</func:function>

	<xsl:template match="*" mode="fl_to-string">
		<!-- start tag : open -->
		<xsl:text>&lt;</xsl:text>
		<xsl:value-of select="name()"/>

		<!-- attributes -->
		<xsl:apply-templates select="@*" mode="fl_to-string"/>

		<!-- start tag : close -->
		<xsl:text>&gt;</xsl:text>

		<!-- content -->
		<xsl:apply-templates select="* | text()" mode="fl_to-string"/>

		<!-- close tag -->
		<xsl:value-of select="concat('&lt;/',name(),'&gt;')"/>
	</xsl:template>

	<xsl:template match="@*" mode="fl_to-string">
		<xsl:value-of select="concat(' ',name(),'=&#34;',.,'&#34;')"/>
	</xsl:template>




</xsl:stylesheet>

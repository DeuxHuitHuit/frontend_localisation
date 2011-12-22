<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:fl="http://symphony-cms.com/functions"
	xmlns:func="http://exslt.org/functions"
	extension-element-prefixes="func">
	
	
	<!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	#Translation utilities
	
	version:  1.0
	author:   Vlad Ghita
	email:    vlad_micutul@yahoo.com
	
	
	To use these functions add the "fl" namespace to your master (or wherever you feel 
	it suits best) stylesheet and import this utility. Add the namespace to all stylesheets 
	where you want to use these functions.

	Usage:
	    <xsl:stylesheet version="1.0" 
	        xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	        xmlns:fl="http://symphony-cms.com/functions"
	        extension-element-prefixes="fl">
	    	
	        <xsl:import href="fl_utilities.xsl" />
	        ...
	    </xsl:stylesheet>
	
	Display translation value:
	    <xsl:copy-of select="fl:__([XPath]/[@handle])" />
	    
	[XPath] = the XPath from "/data/fl-translations" to "item"
	[@handle] = handle of desired item
	
	
	=== Examples ===
	
	Given this XML:
	
	<fl-translations>
	    <page-not-found>
	        <deeper>
	            <item handle="title">Page not found</item>
	            <item handle="message">
	                &lt;p&gt;Requested page was not found. Return &lt;b&gt;home&lt;/b&gt; to continue.&lt;/p&gt;
	            </item>
	        </deeper>
	    </page-not-found>
	    <item handle="site-name">Craiasa Branului in Transylvania - Romania</item>
	</fl-translations>
	
	
	Take a guess what these do:
	
	<xsl:copy-of select="fl:__('page-not-found/deeper/title')" />
	<xsl:copy-of select="fl:__('page-not-found/deeper/message')" />
	<xsl:copy-of select="fl:__('site-name')" />
	
	~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
	
	
	
	<func:function name="fl:__">
		<!-- 
			It's made of 2 parts: [XPath]/[@handle]
			
			[XPath] = the XPath from "/data/fl-translations" to "item"
			[@handle] = handle of desired item
		 -->
		<xsl:param name="context" select="''" />
		
		<func:result>
			<xsl:apply-templates select="/data/fl-translations" mode="fl_translate">
				<xsl:with-param name="context" select="$context" />
			</xsl:apply-templates>
		</func:result>
	</func:function>
	
	
	<xsl:template match="*" mode="fl_translate">
		<xsl:param name="context" />
		
		<xsl:choose>
			<xsl:when test="contains($context, '/')">
				<xsl:apply-templates select="./*[name() = substring-before($context, '/')]" mode="fl_translate">
					<xsl:with-param name="context" select="substring-after($context, '/')" />
				</xsl:apply-templates>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="./item[ @handle = $context ]" disable-output-escaping="yes" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	
	
	
</xsl:stylesheet>
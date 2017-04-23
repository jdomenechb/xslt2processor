<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    <xsl:attribute-set name="paragraph-style">
        <xsl:attribute name="font-decoration">underline</xsl:attribute>
        <xsl:attribute name="font-size">14px</xsl:attribute>
    </xsl:attribute-set>
    <xsl:attribute-set name="span-style">
        <xsl:attribute name="font-weight">bold</xsl:attribute>
        <xsl:attribute name="font-size">1.1em</xsl:attribute>
    </xsl:attribute-set>

    <xsl:template match="/"></xsl:template>
</xsl:stylesheet>
/**
 * SyntaxHighlighter
 *
 * @license
 * Dual licensed under the MIT and GPL licenses.
 */
;(function()
{
    // CommonJS
    typeof(require) != 'undefined' ? SyntaxHighlighter = require('shCore').SyntaxHighlighter : null;

    function Brush()
    {
        var funcs	=	'attribute constant cycle date dump parent random range template_from_string render ez_render_field';

        var tags =	'autoescape endblock block do embed extends filter flush for from if import include macro sandbox set spaceless use verbatim' +
            'endautoescape enddo endembed endfilter endfor endif endmacro endsandbox endset endverbatim' +
            'stylesheets endstylesheets javascripts endjavascripts';

        var filters	= 'abs capitalize convert_encoding date date_modify default escape first format join json_encode keys last length lower merge nl2br number_format raw replace reverse slice sort split striptags title trim upper url_encode';

        var scriptTags = { left: /{(?=(%|#|{))/g, right: /[%#}]}/g };
        tags="block|endblock|extends";

        this.regexList = [
            { regex: /#[\s\S]*/gmi,	css: 'comments' },                          		//comments
            { regex: SyntaxHighlighter.regexLib.doubleQuotedString,		css: 'string' },	// double quoted strings
            { regex: SyntaxHighlighter.regexLib.singleQuotedString,		css: 'string' },	// single quoted strings
            //{ regex: new RegExp(tags, 'gmi'),	css: 'keyword' },			// constants
            { regex: new RegExp(this.getKeywords(tags), 'gmi'),	css: 'keyword' },			// constants
            { regex: new RegExp(this.getKeywords(funcs), 'gmi'),		css: 'variable' },	// common functions
            { regex: new RegExp(this.getKeywords(filters), 'gm'),		css: 'variable' },	// keyword
            { regex: /[a-z0-9._]+/gi,						css: 'variable' }	// variables

        ];

        this.forHtmlScript(scriptTags);
    };

    Brush.prototype	= new SyntaxHighlighter.Highlighter();
    Brush.aliases	= ['twig'];

    SyntaxHighlighter.brushes.Twig = Brush;

    // CommonJS
    typeof(exports) != 'undefined' ? exports.Brush = Brush : null;
})();

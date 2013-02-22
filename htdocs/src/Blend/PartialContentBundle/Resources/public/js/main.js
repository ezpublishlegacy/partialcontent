
$(function () {
    $('pre.code_text').attr('class','brush:plain');
    $('pre.code_twig').attr('class','brush:twig; html-script: true');
    $('pre.code_php').attr('class','brush:php');
    $('pre.code_yml').attr('class','brush:yml');
    $('pre.code_shell').attr('class','brush:bash');

    //SyntaxHighlighter.defaults['html-script'] = true;
    SyntaxHighlighter.all();
});

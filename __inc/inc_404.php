<?php

require '../../__inc/_1header.php';
/***************************************************************/



log2file('srpriz', ['type' => 'error404']);

$txt = explode('*', txarr('blocks', 'error404'));





echo '<pre class="normal" style="font-size: 60%; margin:30px 0; line-height: 1.1;">
─── ── ── ── ── ── ── ─ ── ── ── ── ── ── ── ─ ── ── ── ── ── ── ── ─
─██ ██ ██ ── ██ ██ ██ ─ ██ ██ ██ ██ ██ ██ ██ ─ ██ ██ ██ ── ██ ██ ██ ─
─██ ░░ ██ ── ██ ░░ ██ ─ ██ ░░ ░░ ░░ ░░ ░░ ██ ─ ██ ░░ ██ ── ██ ░░ ██ ─
─██ ░░ ██ ── ██ ░░ ██ ─ ██ ░░ ██ ██ ██ ░░ ██ ─ ██ ░░ ██ ── ██ ░░ ██ ─
─██ ░░ ██ ── ██ ░░ ██ ─ ██ ░░ ██ ── ██ ░░ ██ ─ ██ ░░ ██ ── ██ ░░ ██ ─
─██ ░░ ██ ██ ██ ░░ ██ ─ ██ ░░ ██ ── ██ ░░ ██ ─ ██ ░░ ██ ██ ██ ░░ ██ ─
─██ ░░ ░░ ░░ ░░ ░░ ██ ─ ██ ░░ ██ ── ██ ░░ ██ ─ ██ ░░ ░░ ░░ ░░ ░░ ██ ─
─██ ██ ██ ██ ██ ░░ ██ ─ ██ ░░ ██ ── ██ ░░ ██ ─ ██ ██ ██ ██ ██ ░░ ██ ─
─── ── ── ── ██ ░░ ██ ─ ██ ░░ ██ ── ██ ░░ ██ ─ ── ── ── ── ██ ░░ ██ ─               __
─── ── ── ── ██ ░░ ██ ─ ██ ░░ ██ ██ ██ ░░ ██ ─ ── ── ── ── ██ ░░ ██ ─             <(o )___       _      _      _
─── ── ── ── ██ ░░ ██ ─ ██ ░░ ░░ ░░ ░░ ░░ ██ ─ ── ── ── ── ██ ░░ ██ ─              ( ._> /     <(.)__ >(.)__ =(.)__
─── ── ── ── ██ ██ ██ ─ ██ ██ ██ ██ ██ ██ ██ ─ ── ── ── ── ██ ██ ██ ─               `---\'       (___/  (___/  (___/
─── ── ── ── ── ── ── ─ ── ── ── ── ── ── ── ─ ── ── ── ── ── ── ── ─
</pre>';

echo '<pre class="normal" style="font-size: 300%;">'.$txt[0].'</pre>';

echo '<pre class="normal" style="font-size: 150%;">'.$txt[1].'</pre>';




/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';

exit;
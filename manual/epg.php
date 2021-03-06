<?php
require '_head.php';
?>








<?=sct_start('intro')?>

    <p class="lead">
        ЕПГ (енг. <i>Electronic Program Guide</i>) је <b>дневни</b> распоред програма.<br>
        Основни елементи од којих се састоји ЕПГ су емисије, филмови и серије, те маркетинг и промо блокови.
    </p>


    <p>
        Основни приказ је <b>Листа</b>, то је проста листа елемената у ЕПГ-у, без детаљних података о
        одређеном елементу.
    </p>
    <p>
        Приказ <b>Стабло</b> изаберите ако желите да се сваки елемент ЕПГ-а може отворити у подтабелу
        тако да се виде и његови елементи.
    </p>
    <p>
        <b>ЕГО</b> приказ је прилагођен првенствено кајронисти, ту су приказани сви ЕГО налози.<br>
    </p>
    <p>
        <b>Инџест</b> приказ приказује само снимке за читав ЕПГ, то јест филмове, серије и снимљене емисије.
        Овдје се уноси трајање сваког снимка, чим буде спреман у инџесту.
    </p>
    <p>
        <b>Веб</b> приказ приказује ЕПГ какав ће бити објављен на веб сајту
        (филтрирају се само праве емисије, те филмови и серије).
    </p>
    <p>
        На крају, приказ <b><span class="glyphicon glyphicon-trash"></span> (Корпа)</b> је листа обрисаних елемената ЕПГ-а,
        дакле нешто слично као <i>Recycle Bin</i>. Кад неки елемент искључите из ЕПГ-а, не брише се одмах него
        прво долази у ову корпу, и тек ако га избришете из корпе онда се заиста брише.
    </p>


<?=sct_sub()?>

    <p>
        ЕПГ ћете пронаћи по <b>датуму</b>.<br>
        Ако је датум <b>данас</b>, ЕПГ је наравно на страници <b>Данас</b>.<br>
        Ако је датум <b>сутра или послије</b>, потражите на страници <b>План</b>.<br>
        Ако вам треба ЕПГ за неки од <b>претходних дана</b>, онда погледајте под <b>Архива</b>.
    </p>

    <img src="img/epg/epg_plan.gif" class="img-responsive img_btm_mrg">

    <p>
        На страници <b>ПЛАН</b>, приказују се ЕПГ за све датуме који су актуелни, то јест
        сви који слиједе од данас па даље.
        На слици, датуми почевши од 17. фебруара су сиви, што значи да за те датуме ЕПГ није унесен.
    </p>
    <p>
        За ЕПГ који су унесени, испод датума (узгред, датум је линк на приказ ЕПГ) су три тастера:
    </p>
    <ul>
        <li>Измјени ЕПГ</li>
        <li>Стање (за објављивање): спремно/неспремно<br>
            (на слици, ЕПГ за 14. и 15. имају црвенкасте квадрате јер им је стање НЕСПРЕМНО)</li>
        <li>Кратка информација &ndash; аутор и посљедње ажурирање</li>
    </ul>
    <p>
        За ЕПГ који још <i>нису</i> унесени, испод датума су друга три тастера:
    </p>
    <ul>
        <li>Започни празан ЕПГ</li>
        <li>Копирај други ЕПГ</li>
        <li>Импортуј ЕПГ шаблон</li>
    </ul>
    <p>
        Ако затреба подсјетити се, задржите курсор на одређеном тастеру и приказаће се мали оквир
        то јест <i>tooltip</i> са описом.
    </p>




<?=sct_end()?>






<?=sct_start('view_list')?>

    <a href="img/epg/epg.gif"><img src="img/epg/epg.gif" class="img-responsive img_btm_mrg"></a>
    <p class="pull-right"><small>
        (Напомена: На слици је због простора ЕПГ скраћен на двадесетак елемената.)
    </small></p>
    <p class="clearfix"></p>

    <p>
        С лијева надесно, прво иде плава колона за <b>термин емитовања</b>.<br>
        <i>Фиксно</i> постављени термини су означени свијетло-плавом, а <i>израчунати</i> термини тамно-плавом бојом.
    </p>

    <p>
        Затим иде колона намијењена за приказивање <b>грешке</b>, то јест одступања од планираног термина.<br>
        Одступање се наравно може десити само ако је термин елемента постављен <i>фиксно</i>.
    </p>

    <p>
        Затим иду двије колоне везане за <b>трајање</b> елемента.
        Главна колона је <i>лијево</i>, она показује <b>важеће</b> трајање.<br>
        У колони <i>десно</i> приказује се <b>неважеће</b> трајање.
    </p>

    <div class="bs-callout bs-callout-primary">
        <h4>"Важеће" и "неважеће" трајање емисије</h4>
        <p>
            Eмисијe <i>уживо</i> обично имају два трајања: <b>планирано</b> трајање, које се поставља фиксно, унапријед,
            приликом планирања дневног програма,
            и <b>израчунато</b> трајање, које се касније, кад се емисија попуни садржајем (вијестима, итд),
            израчуна Продеском на основу трајања свих елемената у тој емисији.<br>
        </p>
        <p>
            Нпр Дневник обично по плану треба трајати око 25 минута. Но, кад су вијести спремне, онда Продеск може
            прилично тачно израчунати <i>реално</i> трајање, које се обично разликује од планираног
            (рецимо, није испало 25:00 него 26:45 минута).
        </p>
        <p>
            Значи, имамо два трајања емисије &ndash; планирано и израчунато, а за рачунање ЕПГ-а може важити само једно.
            Кад уредник оцијени да је емисија <i>готова</i>, нпр кад су све вијести спремне, онда треба притиснути кваку
            испред наслова да би обиљежио да је емисија спремна, и тада предност добија <i>израчунато</i> трајање,
            а до тога важи <i>планирано</i>.
        </p>
        <p>
            Планирано трајање је означено тамном, а израчунато трајање свијетлом зеленом бојом.
        </p>
    </div>

    <p>
        На крају иде главна колона, са врстом и <b>насловом</b> елемента.
    </p>
    <p>
        Ако се ради о <i>емисији</i>, онда уз наслов могу стајати и неке од ових ознака:
    </p>
    <ul>
        <li>
            Зелена квака испред наслова означава да је емисија спремна.
        </li>
        <li>
            Ако се емисија емитује уживо, поред наслова ће бити знак:
            <span class="glyphicon glyphicon-facetime-video" style="color:red"></span>
        </li>
        <li>
            Ако је реприза, биће знак:
            <span style="color:white; background-color:black; padding:1px 3px; font-weight:bold">Р</span>
        </li>
        <li>
            Ако емисију треба у режији снимити за репризу, сасвим десно ће бити знак:
            <span style="color:#956; border:1px solid #956; background-color:#fbc; padding:1px 3px;">Р</span>
        </li>
    </ul>

    <h4>Контроле уз елементе</h4>

    <p>
        У табели садржаја, лијево поред сваког елемента је контрола за убацивање <b>новог</b> елемента у табелу:
        <img src="img/element_insert.gif">
    </p>

    <p>
        Десно од сваког елемента су двије контроле – за <b>искључивање</b> елемента, и за <b>подешавање</b> елемента:
        <img src="img/element_switch.gif"> <img src="img/element_modify.gif">
    </p>



<?=sct_sub()?>

    <p>
        Ако притиснете контролу за убацивање елемента у табелу садржаја, десно ће се отворити трака за избор елемента:
    </p>
    <img src="img/epg/epg_element_bar.gif" class="img-responsive img_btm_mrg">

    <p>
        Врсте елемената су:
    </p>

    <ul>
        <li>Емисија</li>
        <li>Филм</li>
        <li>Серија</li>
        <li>Маркетинг блок</li>
        <li>Промо блок</li>
        <li>Клип – то је било какав неодређени снимак, нпр џингл, химна, и тсл.</li>
    </ul>

    <p>
        Има и пар помоћних елемената:
    </p>

    <ul>
        <li>Напомена – то је кратко упутство или упозорење које ће се приказати као посебна линија у ЕПГ-у</li>
        <li>Простор – може затребати кад у програму постоји рупа за коју тренутно није
            одлучено каквим ће се садржајем попунити</li>
    </ul>

    <p>
        На крају, елемент који одвојено треба споменути је <b>линк</b>. То је линк на емисију која се налази на неком
        другом мјесту, нпр која се преноси са другог канала.
    </p>



<?=sct_sub()?>

    <p>
        На врху, изнад траке са насловом, налази се трака са контролама.
        С лијева надесно, прво иде <b>сат</b>, затим група тастера за бирање <b>врсте приказа</b>,
        затим <b>филтер елемената</b>, и на крају тастер за <b>измјену садржаја</b>.

    </p>

    <img src="img/epg/epg_ctrlbar.gif" class="img-responsive img_btm_mrg">

    <h4>Врсте приказа</h4>

    <p>
        Основни приказ је <b>Листа</b>, то је проста листа елемената у ЕПГ-у, без детаљних података о
        одређеном елементу. (Ова врста приказа је приказана и објашњена изнад.)
    </p>
    <p>
        Приказ <b>Стабло</b> изаберите ако желите да се сваки елемент ЕПГ-а може отворити у подтабелу
        тако да се виде и његови елементи.
    </p>
    <p>
        <b>ЕГО</b> приказ је прилагођен првенствено кајронисти, ту су приказани сви ЕГО налози, за читав дан.<br>
    </p>
    <p>
        <b>Веб</b> приказ приказује ЕПГ какав ће бити објављен на веб сајту
        (филтрирају се само праве емисије, те филмови и серије).
    </p>
    <p>
        Приказ <b><span class="glyphicon glyphicon-trash"></span> (Корпа)</b> је нешто слично као <i>Recycle Bin</i>
        за елементе ЕПГ-а. Кад их искључите из табеле садржаја, не бришу се него долазе у корпу,
        и тек кад их избришете из корпе онда се заиста бришу.
    </p>

<?=sct_end()?>

<?=sct_start('view_tree')?>

    <p>
        Приказ <b>Стабло</b> изаберите ако желите да се сваки елемент ЕПГ-а може отворити у подтабелу
        тако да се виде и његови елементи.
    </p>

    <p>
        На десном крају сваког реда у табели садржаја појавиће се тастер за отварање подтабеле.
        На слици је елемент са отвореном подтабелом.
        Као примјер је узет један маркетинг блок који садржи шест елемената – на почетку и на крају шпица,
        и између њих четири маркетинг предмета, то јест рекламе.
    </p>

    <img src="img/epg/epg_tree.gif" class="img-responsive img_btm_mrg">

    <p>
        Тастер СТАБЛО служи и као прекидач за отварање/затварање свих елемената.
        Значи, ако желите отворити подтабеле за <b>све</b> елементе, притисните опет тастер СТАБЛО.
    </p>

<?=sct_end()?>

<?=sct_start('view_cvr')?>

    <p>
        Овај приказ је прилагођен првенствено кајронисти, ту су приказани сви ЕГО налози, за читав дан.
    </p>
    <p>
        Сваки ЕГО налог има пречицу да кајронист означи да је преузео налог (квака испред наслова).
        Зелена квака је знак да је посао већ у току или завршен, па према томе аутор не би требао даље
        мијењати садржај налога јер може доћи до неспоразума.
    </p>

    <div class="bs-callout bs-callout-default">
        <h4>Филтер</h4>
        У траци са контролама, филтер елемената ради другачије у односу на приказ Листа или приказ Стабло:
        можете филтрирати одређену врсту ЕГО налога, на примјер само потписе или само кролове, и тсл.
    </div>

<?=sct_end()?>

<?=sct_start('view_ingest')?>

    <p>
        Приказ ИНЏЕСТ приказује само снимке из читавог ЕПГ-а, то јест филмове, серије и снимљене емисије.
    </p>

    <img src="img/epg/epg_ingest.gif" class="img-responsive img_btm_mrg">

    <p>
        Трајање сваког снимка се може уносити на самој страници. Довољно је притиснути на трајање и текст ће се претворити
        у поље за измјене. На слици је на трећем скопу отворено поље за измјену.
    </p>

    <p>
        Трајање се уноси у формату <b>чч:мм:сс</b>.<br>
        Ако желите, можете изоставити нуле на почетку или двотачку између часова, минута и секунди.
        На примјер, умјесто "01:12:47" можете унијети "11247".. умјесто "00:57:19" можете унијети "5719", итд.
    </p>

<?=sct_end()?>

<?=sct_start('view_web')?>

    <p>
        Овај приказ приказује ЕПГ какав ће бити објављен на веб сајту
        (филтрирају се само праве емисије, те филмови и серије).
    </p>

    <img src="img/epg/epg_web.gif" class="img-responsive img_btm_mrg">

    <p>
        У плавој колони је термин емитовања, заокружен на минуте.
    </p>
    <p>
        Лијево, на почетку сваког реда, је зеленом или црвеном бојом означено да ли емисија иде на <i>веб стриминг</i>,
        то јест да ли ће се емитовати преко веб сајта.
    </p>
    <p>
        Емисије које треба снимити за <i>Video-On-Demand (VOD)</i>, то јест које ће бити постављене у видео рубрици
        на веб сајту, обиљежене су десно, знаком <span class="glyphicon glyphicon-download webvod"></span>.
    </p>
    <p>
        Инструкције да ли ће се емисија емитовати на вебу и да ли је треба снимати за видео рубрику, читају се из
        правила подешених за емисију. Дакле, нема потребе да се уносе за свако емитовање. Филмови и серије се по дефолту
        не емитују и не снимају, због ауторских права.<br>
        Ипак, у ЕПГ-у се може за сваки елемент подесити <i>изузетак</i> од дефолт правила.
        На примјер, одређена емисија може по правилу бити подешена да се не емитује преко веба, али за одређену епизоду
        те емисије можемо одлучити да је емитујемо.
        Или, за одређени филм (деси се на примјер за документарне филмове) можемо подесити да се емитује
        преко веба и сними за видео рубрику, иако се филмови генерално не емитују на вебу и не снимају.
    </p>

<?=sct_end()?>

<?=sct_start('view_trash')?>

    <p>
        Кад <b>искључите</b> неки елемент у табели садржаја (користећи контролу десно од линије елемента), та линија се престаје
        урачунавати али се и даље приказује у табели садржаја (прекривена је сивом бојом), и можете је поново укључити
        ако желите. На тај начин планер програма или реализатор може на примјер држати пар промо блокова у резерви,
        или може нпр провјерити како би се уклопио ЕПГ ако би искључили неки блок или преглед програма, и тсл.
    </p>

    <p>
        Ако искључени елемент желите сасвим <b>избрисати</b> из ЕПГ-а, то можете урадити у приказу КОРПА. У корпу
        долазе сви елементи који су искључени у ЕПГ-у. Кад сте сигурни да вам искључени елемент дефинитивно неће
        требати у ЕПГ-у, онда га избришите из корпе.
    </p>

<?=sct_end()?>






<?=sct_start('epg_new')?>

    <p>
        Отворите страницу <b>ПЛАН</b>, гдје је листа ЕПГ. За ЕПГ који још нису унесени, датуми су сиви, и испод датума
        су три тастера за три начина како да додате нови ЕПГ:
    </p>
    <img src="img/epg/epg_plan_noepg.gif" class="img_btm_mrg" style="border: 1px solid #ddd; float:left; margin-right:30px;">

    <ul>
        <li>Започни празан ЕПГ</li>
        <li>Копирај други ЕПГ</li>
        <li>Импортуј ЕПГ шаблон</li>
    </ul>

    <div class="clearfix"></div>

    <h4>Празан ЕПГ</h4>
    <p>
        Отвара се (празна) <a href="#sct_epg_change_0">табела са елементима</a>,
        у коју онда додајете елементе један по један. Значи, читав ЕПГ састављате испочетка.
    </p>

    <h4>Други ЕПГ</h4>
    <p>
        Прво се отвара страница са <i>ЕПГ календаром</i>, да изаберете ЕПГ који желите копирати, а затим добијате
        табелу са елементима, али попуњену елементима ЕПГ-а који сте изабрали.
        Елементе наравно можете подешавати, брисати, додавати нове..<br>
        Обично има смисла копирати неки од прошлих ЕПГ за исти дан у недјељи, нпр ако правимо ЕПГ за уторак онда је
        вјероватно лакше ископирати ЕПГ од претходног уторка па исправити разлике, него састављати испочетка.
    </p>

    <h4>ЕПГ шаблон</h4>
    <p>
        Прво се отвара страница са <i>листом ЕПГ шаблона</i>, да изаберете шаблон који желите импортовати,
        а затим добијате табелу са елементима, али попуњену елементима шаблона који сте изабрали.
    </p>

    <div class="bs-callout bs-callout-primary">
        <h4>Филмске серије</h4>
        <p>
            Када нови ЕПГ додајете на основу другог ЕПГ-а или на основу ЕПГ шаблона, Продеск ће аутоматски
            инкрементовати (повећати за један) епизоде у свим серијама. На тај начин ће вам уштедити нешто труда.<br>
            Ипак, за сваки случај увијек прегледајте јесу ли епизоде исправно одређене.
        </p>
    </div>

<?=sct_sub()?>
    <p>
        На дну странице са садржајем ЕПГ-а је трака са <i>споредним</i> контролама. Сасвим десно у траци је тастер за
        брисање ЕПГ-а:
    </p>

    <img src="img/epg/epg_btm_ctrlbar.gif" class="img-responsive img_btm_mrg">


<?=sct_end()?>



<?=sct_start('epg_change')?>

    <p>
        Садржај ЕПГ-а се може мијењати на два начина:
    </p>
    <p>
        Прво, може се мијењати читава <b>табела са елементима</b>, ту су отворени за измјене сви елементи одједном.
    </p>
    <p>
        Друго, може се мијењати само <b>одређени елемент</b>, дакле сваки елемент појединачно.
    </p>
    <p>
        Према томе, кад желите мијењати <b>више елемената одједном</b> онда је боље отворити читаву табелу са елементима,
        а кад желите мијењати <b>само један</b> елемент (или додати елемент изнад/испод) онда је боље користити контроле
        на том одређеном елементу, јер је тако брже.<br>
        <b>Редослијед</b> елемената можете мијењати само у табели са елементима.
    </p>

<?=sct_sub()?>

    <p>
        Кад отворите ЕПГ, да бисте мијењали табелу са садржајем притисните тастер
        <span class="glyphicon glyphicon-cog"></span> <b>САДРЖАЈ</b> (десно у траци са контролама).<br>
        Такође, ако сте отворили страницу ПЛАН, имате тастер са знаком <span class="glyphicon glyphicon-cog"></span>
        (први лијево) на квадрату сваког унесеног ЕПГ-а.
    </p>
    <p>
        У табели са елементима може се подешавати било који
        елемент, и могу се додавати нови елементи, и такође може се мијењати редослијед елемената.
    </p>

    <img src="img/epg/epg_mdf_multi.gif" class="img-responsive img_btm_mrg">

    <h4>Колоне</h4>

    <p>
        У првој колони је <b>врста</b> елемента. Ова колона служи и за <b>премјештање</b> елемента (<i>drag&drop</i>).
    </p>

    <p>
        Друга колона (плава) је за фиксни <b>термин</b>.
    </p>

    <p>
        Трећа колона (зелена) је за фиксно или планирано <b>трајање</b>.
    </p>

    <p>
        На крају, главна колона је за бирање самог предмета из каталога, то јест одређене емисије, филма, серије,
        маркетинг или промо блока, што зависи од врсте елемента.<br>
        За емисије, у овој колони је још и поље за тему, и контрола за избор да ли је емисија
        <i>уживо</i> или <i>снимак</i> или <i>реприза</i>.
    </p>

    <div class="bs-callout bs-callout-primary">
        <h4>Пречице</h4>
        У пољима за <i>термин</i> и пољима за <i>трајање</i> можете користити пречице на тастатури:
        <ul style="margin-bottom:0">
            <li>Притисните <b>минус</b> (-) да избришете читав термин или трајање</li>
            <li>Притисните <b>тачку</b> или <b>зарез</b> (.,) да пребаците курсор у сљедеће поље</li>
            <li>Притисните <b>звјездицу</b> (*) да читав термин означите као &quot;на чекању&quot;</li>
        </ul>
    </div>

    <h4>Контроле</h4>

    <p>
        Лијево поред сваког елемента су контроле за додавање <b>новог</b> елемента изнад или испод тог реда у табели:
        <img src="img/element_insert.gif">
    </p>
    <p>
        Десно од сваког елемента су двије контроле – за <b>искључивање</b> елемента, и за <b>дуплирање</b> елемента:
        <img src="img/element_switch.gif"> <img src="img/element_duplicate.gif">
    </p>
    <p>
        Кад искључите елемент на страници за измјене, он ће приликом снимања форме одмах бити избрисан из ЕПГ-а,
        дакле не иде у <i>корпу</i>.
    </p>

    <p>
        Дуплирање елемента ће направити идентичан елемент, који онда можете премјестити на друго мјесто у табели.
        На тај начин можете уштедити вријеме око дефинисања елемената када се у ЕПГ-у понавља иста емисија или
        серија.
    </p>

    <div class="bs-callout bs-callout-primary">
        <h4>Термин емитовања &quot;на чекању&quot;</h4>
        Кад је термин емитовања <i>непознат</i> (нпр ако емисија треба почети након неког директног преноса
        коме се трајање не зна засигурно), па према томе не можете унијети фиксни термин, а такође не желите да се приказује
        ни термин израчунат Продеском, онда можете означити да је емисија "на чекању", па да се умјесто термина
        прикажу звјездице.<br>
        И емисије које слиједе, све до прве емисије која има <i>фиксни</i> термин, такође ће бити означене као
        "на чекању".
    </div>

<?=sct_sub()?>

    <p>
        Уз сваки елемент у табели садржаја, десно и лијево су контроле помоћу којих можете подешавати тај елемент,
        или додати нови елемент у ред изнад или испод.
    </p>

    <img src="img/epg/epg_mdf_elm.gif" class="img-responsive img_btm_mrg">

    <p>
        Преко контроле/тастера за подешавање елемента <img src="img/element_modify.gif"> отвара се страница
        за измјене/подешавање. Страница се разликује, зависно од врсте елемента. За елемент типа <i>емисија</i>,
        отвориће се уствари иста страница као кад на страници сценарија емисије кликнете на тастер за подешавање
        <a href="scnr.php#sct_scnr_change_0">описа сценарија</a> &ndash; страница са веома опширним опцијама.
        За друге елементе, листа опција је много једноставнија. На слици је страница за елемент типа <i>серија</i>:
    </p>

    <img src="img/epg/epg_mdf_single.gif" class="img-responsive img_btm_mrg">

    <p>
        Подаци које можете подешавати су углавном исти као кад отворите табелу са елементима.
        Једина разлика је што овдје можете додати и малу напомену уз елемент &mdash; етикету/<i>tooltip</i>,
        која се приказује на десној страни елемента кад се отвори ЕПГ и може
        бити згодна да се нпр пренесе кратка информација колегама из режије..
    </p>


    <h4>Пречице у табели садржаја ЕПГ-а</h4>

    <p>
        Још један начин за појединачно подешавање елемента су пречице у табели садржаја (на страници за преглед ЕПГ-а),
        тачније у колонама за термин и за трајање.
    </p>

    <div class="bs-callout bs-callout-primary">
        <h4>Фиксни термини емитовања</h4>
        Обично се термин емитовања елемента <b>фиксно</b> одређује само ако емисија <b>мора</b> почети
        баш у одређеном термину, па према томе трајање претходних елемената мора да се уклопи према њој.
        За све друге емисије термини емитовања елемената се уопште не постављају у ЕПГ-у,
        него их Продеск <i>израчунава</i> за сваки елемент, на основу трајања претходних елемената.
    </div>

    <p>
        <b>Постављање фиксног термина</b>
    </p>

    <p>
        У <b><i>колони за термин</i></b> је пречица за постављање <b>фиксног</b> термина емитовања елемента.
        Кад притиснете на одређени термин, отвара се плави оквир за измјене:
    </p>

    <img src="img/epg/epg_mdf_short.gif" class="img-responsive img_btm_mrg">

    <p>У поље за промјену термина укуцајте нови термин. Да потврдите промјену притисните тастер ENTER,
        а да одустанете притисните ESC.</p>
    <p><span class="glyphicon glyphicon-bell"></span> је пречица за постављање термина на тренутно вријеме,
        то јест <i>сада</i>.</p>
    <p><span class="glyphicon glyphicon-remove"></span> је пречица за <i>брисање</i> фиксног термина
        (активна је једино ако је термин претходно уопште постављен фиксно).</p>
    <p><span class="glyphicon glyphicon-asterisk"></span> је пречица да се термин означи као
        <i>&quot;на чекању&quot;</i>.</p>

    <p>
        <br><b>Брисање планираног трајања</b>
    </p>

    <p>
        Пречица у <b><i>колони за трајање</i></b> брише <i>планирано</i> трајање,
        да би <i>израчунато</i> трајање заузело његово мјесто.<br>
        Деси се нпр да за одређени елемент типа маркетинг блок, приликом планирања буде одвојено рецимо 01:30 минута,
        а да се при том наравно не зна који тачно блок ће ту бити распоређен. И послије, када надлежна служба ту
        постави конкретан маркетинг блок, са другачијим трајањем, онда ће у ЕПГ-у и даље предност имати планирано
        трајање иако већ имамо реално израчунато трајање тог конкретног маркетинг блока. У тој ситуацији, најбрже је
        употријебити ову пречицу да се једним кликом избрише планирано трајање које више није актуелно.<br>
        Пречица је активна једино ако елемент има и планирано и израчунато трајање, и да је при том планирано трајање
        тренутно <i>важеће</i>.
    </p>

<?=sct_sub()?>

    <p>
        Очигледно, ЕПГ желимо објавити на веб сајту тек кад је спреман. Пошто се објављивање ЕПГ-а ради аутоматски,
        додата је опција да се стање обиљежи као спремно или неспремно за објављивање.
    </p>

    <p>
        Какво је тренутно стање ЕПГ-а, можете видјети на страници ПЛАН &ndash; ако ЕПГ није спреман за објављивање
        онда је његов квадрат обојен црвено. Или, кад отворите одређени ЕПГ &ndash; испред наслова је квака, ако је сива
        онда није спреман, а ако је зелена онда јесте спреман.
    </p>

    <p>
        Стање сваког ЕПГ-а можете промијенити на страници ПЛАН (средњи тастер на квадрату за одређени ЕПГ),
        или већ приликом уноса новог ЕПГ-а (или подешавања постојећег) &ndash; на страници за мијењање
        табеле са елементима, контрола/прекидач за промјену стања ЕПГ-а је на врху десно.
    </p>

<?=sct_end()?>




<?=sct_start('tmpl')?>

    <p>Намјена шаблона је да се скрати посао око састављања новог ЕПГ-а. На примјер, ако је сваког уторка ЕПГ
        релативно сличан, онда се дио садржаја који је сличан, који се сваког уторка понавља, може снимити у шаблон,
        и затим тај шаблон можемо користити као <b>стартну позицију</b> сваки пут када правимо ЕПГ за уторак.
    </p>

    <h4>Како додати нови шаблон</h4>

    <p>
        Шаблон се може додати једино на основу неког постојећег ЕПГ-а. Кад отворите ЕПГ, на дну, испод табеле са
        садржајем, је трака са споредним контролама. Ту је и тастер да се од тог ЕПГ-а направи шаблон:
    </p>
    <img src="img/epg/epg_btm_ctrlbar2.gif" class="img-responsive img_btm_mrg">

    <p>
        Кад притиснете тастер, отвориће се табела са елементима тог ЕПГ-а, с тим да ће маркетинг и промо блокови бити
        празни (јер се претпоставља да се исти блокови не користе током више дана, али ће на њиховом мјесту бити
        неки други блокови, па према томе <i>мјесто</i> ипак треба сачувати).
        По жељи прилагодите елементе, избришите оне који су непотребни, то јест који се не понављају, итд,
        и попуните поље за наслов шаблона (на врху, у заглављу), и затим можете снимити шаблон.
    </p>


    <h4>Листа шаблона</h4>

    <p>
        На страници ШАБЛОНИ је листа шаблона. Кад отворите одређени шаблон, можете га подешавати на потпуно исти начин
        као и било који ЕПГ. Можете га избрисати преко тастера у траци са контролама на дну, испод табеле са садржајем.
    </p>

    <p>
        Шаблон користите тако што приликом додавања новог ЕПГ-а изаберете трећи тастер, за импорт ЕПГ шаблона.
        Приказаће се листа шаблона, и кад изаберете одређени шаблон, он ће бити импортован у ЕПГ.
    </p>

<?=sct_end()?>







<?=sct_start('premiere_rerun')?>

    <p>
        Кад год у ЕПГ додате елемент (емисију, филм/серију) који је означен као <b>реприза</b>,
        Продеск ће покушати да пронађе на који елемент се односи та реприза, то јест од чега је то реприза, који
        елемент је био <b>премијера</b> (прво емитовање).
    </p>
    <p>
        Ако успије да пронађе премијеру, Продеск ће направити везу између репризе и премијере, и онда ће у ЕПГ-у
        приказивати линк &ndash; квадратић са знаком "&" и на једној и на другој страни.
        На премијери линк ка репризи, а на репризи линк ка премијери.
    </p>
    <p>
        Тиме се олакшава проналажење снимка за репризу. Кад колеге у режији знају на шта се односи реприза,
        онда им је лакше да пронађу потребни снимак емисије (или филма/серије).
    </p>
    <p>
        Кад задржите курсор на том линку, приказује се етикета (<i>tooltip</i>) са термином елемента на који линк упућује.
        Ако се деси да је веза погрешна, да програм није исправно пронашао премијеру, онда отворите страницу за измјене
        (за елемент који је реприза) и у блоку <i>Премијера</i> имате контроле да исправите везу или да је избришете.
    </p>

<?=sct_end()?>






<?php
require '_foot.php';
<?php

$manz = [
    'index' => ['cpt' => 'Садржај', 'icon' => '<span class="glyphicon glyphicon-book"></span>'],
    'video' => ['cpt' => 'Видео', 'icon' => '<span class="glyphicon glyphicon-film" style="color: #00f"></span>'],
    'basic' => ['cpt' => 'Основно'],
    'desk'  => ['cpt' => 'Деск'],
    'scnr'  => ['cpt' => 'Сценарио'],
    'epg'   => ['cpt' => 'ЕПГ'],
    'film'  => ['cpt' => 'Филм'],
    'block' => ['cpt' => 'Блокови', 'disable' => true],
];

if (IS_DEV) {
    $manz['admin'] = ['cpt' => 'Админ'];
    //$manz['server'] = ['cpt' => 'Сервер', 'disable' => true];
}



$title_app = 'Продеск';
$title_instr = 'Упутство';

$video_note = 'Подесите квалитет слике <b>720p(HD)</b>';

$img_logo = '_img.php?file=logo_company.gif';




function get_nav_subz($sctn) {

    switch ($sctn) {


        case 'basic':

            $nav = [
                'intro' => [
                    'sct' => 'Увод',
                    'sub' => [
                        'Р/ТВ програм растављен на атоме',
                        'Ко користи Продеск, и за који посао',
                    ]
                ],
                'login' => [
                    'sct' => 'Како ући и изићи',
                    'sub' => [
                        'Како отворити програм (Login)',
                        'Излаз из програма (Logout)',
                    ]
                ],
                'navbars' => [
                    'sct' => 'Сналажење (Навигација)',
                ],
                'list' => [
                    'sct' => 'Каталог – страница за листу података',
                    'sub' => [
                        'Примјери',
                        'Траке са контролама (тастери и филтери)',
                        'Листа података (резултати)',
                    ]
                ],
                'buttons' => [
                    'sct' => 'Тастери и знакови',
                ],
                'printing' => [
                    'sct' => 'Штампање',
                ],
            ];
            break;


        case 'desk':

            $nav = [
                'intro' => [
                    'sct' => 'Увод',
                ],
                'story' => [
                    'sct' => 'Вијести',
                    'sub' => [
                        'Елементи вијести',
                        'Нова вијест',
                        'Страница одређене вијести',
                        'Повезивање вијести са сценаријем емисије',
                        'Каталог вијести',
                    ]
                ],
                'task' => [
                    'sct' => 'Задаци',
                    'sub' => [
                        'Како написати вијест на основу задатка',
                        'Задаци у каталогу и у сценарију',
                    ]
                ],
                'cvr' => [
                    'sct' => 'ЕГО',
                ],
                'my_desk' => [
                    'sct' => '<span class="glyphicon glyphicon-eye-open"></span>&nbsp; Мој Деск',
                ],
                'sets' => [
                    'sct' => '<span class="glyphicon glyphicon-wrench"></span>&nbsp; (Поставке)',
                    'sub' => [
                        'Брзина читања',
                        'Боје за водитеље',
                    ]
                ],
                'trash' => [
                    'sct' => '<span class="glyphicon glyphicon-trash"></span>&nbsp; (Корпа)',
                ],
                'prgm' => [
                    'sct' => 'Емисије и редакције',
                    'sub' => [
                        'Неактивне емисије',
                    ]
                ],
            ];
            break;


        case 'scnr':

            $nav = [
                'intro' => [
                    'sct' => 'Увод',
                    'sub' => [
                        'Како наћи одређени сценарио',
                    ]
                ],
                'view_list' => [
                    'sct' => 'Приказ ЛИСТА',
                    'sub' => [
                        'Врсте елемената',
                        'Траке са насловом и контролама',
                    ]
                ],
                'view_tree' => [
                    'sct' => 'Приказ СТАБЛО',
                ],
                'view_script' => [
                    'sct' => 'Приказ СКРИПТ',
                ],
                'view_cvr' => [
                    'sct' => 'Приказ ЕГО',
                ],
                'view_rec' => [
                    'sct' => 'Приказ СКОП',
                ],
                'view_prompter' => [
                    'sct' => 'Приказ ПРОМПТЕР',
                ],
                'view_trash' => [
                    'sct' => 'Приказ КОРПА',
                ],
                'scnr_dsc' => [
                    'sct' => 'Измјене података о емисији',
                ],
                'scnr_content' => [
                    'sct' => 'Измјене садржаја сценарија',
                    'sub' => [
                        'Појединачно подешавање или додавање елемента',
                        'Табела за измјене',
                        'Пречице у табели сценарија',
                    ]
                ],
                'scnr_import' => [
                    'sct' => 'Импорт вијести из другог сценарија',
                ],
                'tmpl' => [
                    'sct' => 'Шаблони',
                    'sub' => [
                        'Како додати нови шаблон',
                    ]
                ],
            ];
            break;


        case 'epg':

            $nav = [
                'intro' => [
                    'sct' => 'Увод',
                    'sub' => [
                        'Како наћи одређени ЕПГ',
                    ]
                ],
                'view_list' => [
                    'sct' => 'Приказ ЛИСТА',
                    'sub' => [
                        'Врсте елемената',
                        'Траке са контролама',
                    ]
                ],
                'view_tree' => [
                    'sct' => 'Приказ СТАБЛО',
                ],
                'view_cvr' => [
                    'sct' => 'Приказ ЕГО',
                ],
                'view_ingest' => [
                    'sct' => 'Приказ ИНЏЕСТ',
                ],
                'view_web' => [
                    'sct' => 'Приказ ВЕБ',
                ],
                'view_trash' => [
                    'sct' => 'Приказ КОРПА',
                ],
                'epg_new' => [
                    'sct' => 'Како додати нови ЕПГ',
                    'sub' => [
                        'Како избрисати ЕПГ',
                    ]
                ],
                'epg_change' => [
                    'sct' => 'Како мијењати ЕПГ',
                    'sub' => [
                        'Подешавање преко табеле са елементима',
                        'Појединачно подешавање или додавање елемента',
                        'Стање (за објављивање)',
                    ]
                ],
                'tmpl' => [
                    'sct' => 'Шаблони',
                    'sub' => [
                        'Како додати нови шаблон',
                    ]
                ],
                'premiere_rerun' => [
                    'sct' => 'Веза премијере и репризе',
                ],
            ];
            break;


        case 'film':

            $nav = [
                'list' => [
                    'sct' => 'Каталог',
                    'sub' => [
                        'Трака са тастерима',
                        'Трака са филтерима/контролама',
                        'Колоне у табели са резултатима',
                    ]
                ],
                'film_new' => [
                    'sct' => 'Како додати нови филм',
                ],
                'film_dtl' => [
                    'sct' => 'Страница одређеног филма',
                ],
                'bcasts' => [
                    'sct' => 'Реализована емитовања',
                    'sub' => [
                        'Каталог емитовања за одређени дан',
                        'Бројач и листа емитовања за одређени филм',
                        'Исправљање листе емитовања',
                    ]
                ],
                'channels' => [
                    'sct' => 'Канали',
                    'sub' => [
                        'Праћење емитовања на више канала',
                    ]
                ],
                'contracts' => [
                    'sct' => 'Уговори',
                ],
                'agents' => [
                    'sct' => 'Агенције',
                ],
            ];
            break;


        case 'admin':

            $nav = [
                'install' => [
                    'sct' => 'Instalacija',
                    'sub' => [
                        'FTP',
                        'PHP',
                        'DB',
                    ]
                ],
                'cfg' => [
                    'sct' => 'CFG',
                    'sub' => [
                        'LOCAL: CFG_BOOT',
                        'CFG_VARZ',
                        'CFG_ARRZ',
                        'CFG_TABLEZ',
                        'CFG_SETZ',
                        'Settings tables',
                        'Channel-specific',
                    ]
                ],
                'db' => [
                    'sct' => 'Database tables',
                    'sub' => [
                        'PROD/DEV uptodate',
                        'Charset and collation',
                        'Table Indexes',
                        'Logs tables',
                    ]
                ],
                'programming' => [
                    'sct' => 'Programming',
                    'sub' => [
                        'LIST scripts',
                        'EPG',
                        'Draggable',
                        'TESTER (Dev + Prod)',
                        'LNG',
                        'User Settings pages',
                    ]
                ],
                'server' => [
                    'sct' => 'Server',
                    'sub' => [
                        'Certificates',
                    ]
                ],


            ];
            break;


        case 'video':

            $nav = [
                'basic' => [
                    'sct' => 'Основно',
                    'sub' => [
                        ['Кратак опис програма', 'YOcn6RYqSeA'],
                        ['Логин и сналажење', 'ueqcIR5_s-w'],
                    ],
                ],
                'desk' => [
                    'sct' => 'Деск',
                    'sub' => [
                        ['Нова вијест', 'FRhlqqfc5Jc'],
                        ['Страница одређене вијести', 'WZyGaDkO5jA'],
                        ['Повезивање вијести са сценаријем емисије', '_4kLWZV369I'],
                        ['Каталог вијести', 'Aw-ZXWYDhl8'],
                    ]
                ],
                'scnr' => [
                    'sct' => 'Сценарио',
                    'sub' => [
                        ['Сценарио емисије', 'HkOacfi3rHE'],
                        ['Приказ ЛИСТА', '-dVZYvMj7kE'],
                        ['Приказ СТАБЛО', 'N63zGhCjJGU'],
                        ['Приказ СКРИПТ', 'IPvFcd_dZdw'],
                        ['Приказ ЕГО', 'duYUO6HHrXY'],
                        ['Приказ СКОП', 'jxaRdFe9BR0'],
                        ['Приказ ПРОМПТЕР', 'lL37d7MKfUc'],
                        ['Приказ КОРПА', 'HzM0zp2fzuU'],
                        ['Подаци о емисији', 'QQf2g7SDMUg'],
                        ['Садржај сценарија', '2u0sv52l9hw'],
                        ['Импорт вијести из другог сценарија', 'Oz7l25x5co8'],
                    ]
                ],

            ];
            break;


        default:
            $nav = null;
    }

    return $nav;
}


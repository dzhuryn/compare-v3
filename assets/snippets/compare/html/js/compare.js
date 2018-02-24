var activeClass = compareOptions.activeClass;
var lang = compareOptions['lang'];
var _compareCountFull = [], //общее количество елементов в списке
  _compareCount = []; //количество по категориям


/*
* Работа с куками
* */
// возвращает cookie с именем name, если есть, если нет, то undefined
function getCookie(name) {
    name = name+'list';
    var matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    matches = matches ? decodeURIComponent(matches[1]) : null;
    if( matches === null){
        return {};
    }
    matches = matches.replace(/\+/g, ' ');
    var cookie = matches.replace("} }", "}}");
    cookie = JSON.parse(cookie);
    return cookie;
}
/*
* Запись в куки
* */
function setCookie(name, valueObj, options) {
    name = name+'list';
    options = options || {};
    options.path = '/';
    value = JSON.stringify(valueObj).replace("}}", "} }");

    var expires = options.expires;

    if (typeof expires == "number" && expires) {
        var d = new Date();
        d.setTime(d.getTime() + expires * 1000);
        expires = options.expires = d;
    }
    if (expires && expires.toUTCString) {
        options.expires = expires.toUTCString();
    }

    value = encodeURIComponent(value);

    var updatedCookie = name + "=" + value;

    for (var propName in options) {
        updatedCookie += "; " + propName;
        var propValue = options[propName];
        if (propValue !== true) {
            updatedCookie += "=" + propValue;
        }
    }

    document.cookie = updatedCookie;
}

/*
 Добавление в список нового елемента
 */
function addInCompare(id, parent,listKey ) {
    var cookie = getCookie(listKey);

    if(typeof cookie[parent] === 'undefined' || cookie[parent] === null){
        cookie[parent] = {};
    }
    cookie[parent][id] = true;
    setCookie(listKey, cookie);

}
/*
Удаление из списка
 */
function deleteFromCompare(id, parent,listKey) {
    var cookie = getCookie(listKey);
    delete cookie[parent][id];
    setCookie(listKey, cookie);
}

/*
* Установка обработчиков событий, доп класов
* */
function setActions(listKey) {
    //поиск елементов для получение верхней категории
    var items = [];
    $('.'+listKey+'-list').each(function (ind,elem) {
        var elemId = $(elem).data('id');
        items.push(elemId)
    });

    //утсанока верхней категории и названия категорий
    $.get('compare_parent',{data:items},function (data) {
        data = JSON.parse(data);
        for (var id in data) {
            var value = data[id];
            var elem = $('.'+listKey+'-list[data-id="'+id+'"]');
            elem.attr('data-parent',value['parent']);
            elem.attr('data-parent-title',value['title']);
            elem.addClass('ready-list');
            elem.click(function (e) {
                e.preventDefault();
                var elemId = $(this).data('id');
                var elemParent = $(this).data('parent');
                if (typeof toListCustom === 'function') {  //проверка наличия кастомной функции
                    toListCustom(elemId,elemParent,listKey);
                }
                else{
                    toList(elemId,elemParent,listKey);
                }
            })
        }
    });

    _compareCountFull[listKey] = 0;
    //утсановка класа для выбранных елементов
    var cookie = getCookie(listKey);
    for (var parent in cookie) {
        for (var id in cookie[parent]) {
            if (cookie[parent][id] === true) {
                var elem = $('.' +listKey+ '-list[data-id="' + id + '"]');
                if (elem.length) {
                    elem.addClass(activeClass);
                }
            }
            _compareCountFull[listKey]++;
            if(typeof _compareCount[listKey] === 'undefined'){
                _compareCount[listKey] = {};
            } if(typeof _compareCount[listKey][parent] === 'undefined'){
                _compareCount[listKey][parent] = 0;
            }

            _compareCount[listKey][parent]++;
        }
    }
    updateGlobalClass(listKey)
}
//установкаа глобадбный классов количества
function updateGlobalClass(listKey) {
    console.log(_compareCountFull)
    //общее количество
    var elem = $('.'+listKey+'-list-count').text(_compareCountFull[listKey])
}
/*
* alert об ограничение количества елементов
* */
function showMaxAlert(elem,id,parent,listKey) {
    var text;
    if(parent === 0 )
        text = lang['max'];
    else
        text = lang['maxInCategory'];

    text = text.replace("(max)", _compareCount[listKey][parent]);
    text = text.replace("(title)", elem.data('parent-title'));

    var template = compareOptions['alertTemplate'].replace("(text)", text);
    elem.append(template);
    setTimeout(function () {
        elem.find('.compare-message').remove();
    },1000);


}
function toList(id,parent,listKey) {
    var cookie = getCookie(listKey);
    var elem = $('.'+listKey+'-list[data-id="'+id+'"]');

    if (typeof cookie[parent] !== 'undefined' && cookie[parent] !== null && cookie[parent][id] === true) {
        deleteFromCompare(id,parent,listKey);
        elem.removeClass(activeClass)
        _compareCountFull[listKey] --;
        _compareCount[listKey][parent] --;
    }
    else{

        if(compareOptions['max'][listKey]> 0 && _compareCount[listKey][parent] > (compareOptions['max'][listKey] - 1)){
            if (typeof showMaxAlertCustom === 'function') {  //проверка наличия кастомной функции
                showMaxAlertCustom(elem,id,parent,listKey);
            }
            else{
                showMaxAlert(elem,id,parent,listKey);
            }
            return ;
        }
        addInCompare(id,parent,listKey)
        elem.addClass(activeClass)
        _compareCountFull[listKey] ++;
        _compareCount[listKey][parent] ++;
    }
    updateGlobalClass(listKey);

}


compareOptions.list.split(',').forEach(function (listKey) {
    setActions(listKey)
});
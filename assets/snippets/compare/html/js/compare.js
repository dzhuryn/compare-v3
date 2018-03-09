/**
Список callBack функций
 **/

var compare = {
    cookie:{},
    //default settings:
    compareOptions: {
        list: "compare,desire",
        activeClass: "active",
        max: {"compare": "3", "desire": "0"},
        listSetParent: {"compare": "1", "desire": "1"},
        alertTemplate: "<div class='compare-message'>(text)</div>",
        log:"0",
        listNames: {
            compare: "сравнения",
            desire: "список желаний"
        },
        maxShowMessage: {
            compare: "1",
            desire: "0"
        },
        addShowMessage: {
            compare: "1",
            desire: "0"
        },
        removeShowMessage: {
            compare: "1",
            desire: "0"
        },
        lang: {
            max: "Максимальное количество элементов (max)",
            maxInCategory: "Максимальное количество элементов для категории (title) - (max)",
            add: "Добавлено в (name)",
            remove: "Удалено из (name)"
        }
    },
    compareCountFull:{},
    compareCount:{},
    activeClass:'',
    lang:'',
    // возвращает cookie с именем name, если есть, если нет, то undefined
    getCookie: function (name) {
        this.log("Получены данные из cookie для списка "+name);
        name = name + 'list';
        var matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        matches = matches ? decodeURIComponent(matches[1]) : null;
        if (matches === null) {
            this.log("Данных нет","only");
            return {};
        }
        matches = matches.replace(/\+/g, ' ');
        var cookie = matches.replace("} }", "}}");
        cookie = JSON.parse(cookie);
        this.log(cookie,"only");
        return cookie;
    },
    /** Запись в куки  **/
    setCookie: function (name, options) {
        var valueObj = this.cookie[name];
        this.log("Обновлено cookies для списка "+ compare);
        this.log(valueObj,"only");

        name = name + 'list';
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
    },
    /** Добавление в список нового елемента **/
    addInCompare: function (elem,id, parent, listKey) {
        //функция перед добавлением
        if (typeof compareBeforeAddItem === 'function') {  //проверка наличия кастомной функции
            if(compareBeforeAddItem(elem,id, parent, listKey) !== true){
                return ;
            }
        }

        //проерка лимита
        if(this.compareOptions['max'][listKey]> 0 && this.compareCount[listKey][parent] > (this.compareOptions['max'][listKey] - 1)){
            this.log("В список "+listKey+" не додан элемент id = "+id+", parent = "+parent+ " из за ограничения количества элементов в группе")
            this.showAlert('max',elem,id,parent,listKey)
            return ;
        }

        if (typeof this.cookie[listKey][parent] === 'undefined' || this.cookie[listKey][parent] === null) {
            this.cookie[listKey][parent] = {};
        }
        this.cookie[listKey][parent][id] = true;
        this.log("В список "+listKey+" додан элемент id = "+id+", parent = "+parent);
        this.setCookie(listKey);
        if(elem.length){
            elem.addClass(this.activeClass);
        }
        this.compareCountFull[listKey]++;
        if (typeof this.compareCount[listKey][parent] === 'undefined') {
            this.compareCount[listKey][parent] = 0;
        }
        this.compareCount[listKey][parent]++;

        this.showAlert('add', elem, id, parent, listKey);


        if (typeof compareAfterAddItem === 'function') {  //проверка наличия кастомной функции
            compareAfterAddItem(id, parent, listKey);
        }

    },
    /** Получение родителя для елемента который есть в списке**/
    getParent: function(searchId,listKey){
        for (var parent in this.cookie[listKey]) {
            for (var id in this.cookie[listKey][parent]) {
                if(parseInt(searchId) === parseInt(id)){
                    return parent;
                }
            }
        }
        return null;
    },
    /** Удаление из списка **/
    deleteFromCompare: function (elem,id, listKey) {
        var parent = this.getParent(id,listKey);

        delete this.cookie[listKey][parent][id];
        this.log("Из списка "+listKey+" удалено элемент id = "+id+", parent = "+parent);
        this.setCookie(listKey);

        if(typeof elem.length !== 'undefined' && elem.length){
            elem.removeClass(this.activeClass);
        }
        this.compareCountFull[listKey] --;
        this.compareCount[listKey][parent] --;
        this.showAlert('remove',elem,id,parent,listKey);
        if (typeof compareAfterDeleteItem === 'function') {  //проверка наличия кастомной функции
            compareAfterDeleteItem(id,parent,listKey);
        }

    },
    /** Удаление группы из списка **/
    deleteGroupFromCompare: function (parent, listKey) {
        delete this.cookie[listKey][parent];
        this.setCookie(listKey);
    },
    /** Установка обработчиков событий, доп класов **/
    setActions: function (listKey) {
        //поиск елементов для получение верхней категории
        var items = [];
        var obj = this;
        $('.' + listKey + '-list').each(function (ind, elem) {
            var elemId = $(elem).data('id');
            items.push(elemId)
        });

        //утсанока верхней категории и названия категорий
        $.post('compare_parent', {
            data: items,
            list: listKey,
        }, function (data) {
            data = JSON.parse(data);
            for (var id in data) {
                var value = data[id];
                var elem = $('.' + listKey + '-list[data-id="' + id + '"]');

                //проверка параметра по установка data-parent
                if(parseInt(obj.compareOptions.listSetParent[listKey]) === 1){
                    elem.attr('data-parent', value['parent']);
                    elem.attr('data-parent-title', value['title']);
                }
                elem.addClass('ready-list');
                elem.click(function (e) {
                    e.preventDefault();
                    var elemId = $(this).data('id');
                    var elemParent = $(this).data('parent');
                    if (typeof compareToList === 'function') {  //проверка наличия кастомной функции
                        compareToList(elemId, elemParent, listKey);
                    }
                    else {
                        obj.toList(elemId, elemParent, listKey);
                    }
                })
            }
            obj.log("Параметр data-parent установлен для элементов списка "+listKey);
            compareAfterReady('parent',listKey)
        });

        this.compareCountFull[listKey] = 0;
        this.compareCount[listKey] = {};

        //установка класа для выбранных елементов
        //подсчет количества     
        for (var parent in this.cookie[listKey]) {
            for (var id in this.cookie[listKey][parent]) {
                if (this.cookie[listKey][parent][id] === true) {
                    var elem = $('.' + listKey + '-list[data-id="' + id + '"]');
                    if (elem.length) {
                        elem.addClass(this.activeClass);
                        elem.attr('data-parent',parent);
                    }
                }
                this.compareCountFull[listKey]++;
                if (typeof this.compareCount[listKey][parent] === 'undefined') {
                    this.compareCount[listKey][parent] = 0;
                }

                this.compareCount[listKey][parent]++;
            }
        }
        obj.log("Установлено активные классы для элементов списка "+listKey);
        this.updateGlobalClass(listKey);

        if (typeof compareAfterReady === 'function') {  //проверка наличия кастомной функции
            compareAfterReady('class',listKey);
        }

    },
    /** установкаа глобадбный классов количества **/
    updateGlobalClass: function updateGlobalClass(listKey) {
        //общее количество
        $('.'+listKey+'-list-count').text(this.compareCountFull[listKey]);

        if (typeof compareAfterUpdateGlobalClass === 'function') {  //проверка наличия кастомной функции
            compareAfterUpdateGlobalClass(listKey);
        }

    },
    /** alert об ограничение количества елементов **/
    showAlert: function(type,elem,id,parent,listKey) {
        //проверка надо ли выводить сообщение даного типа
        if(this.compareOptions[type+'ShowMessage'][listKey] === '0' ){
            return
        }
        if (typeof compareShowAlert === 'function') {  //проверка наличия кастомной функции
            compareShowAlert(type,elem,id,parent,listKey);
            return ;
        }
        var text;
        if(parent === 0 && type=== 'max')
            text = this.lang['max'];
        else if(type === 'max')
            text = this.lang['maxInCategory'];
        else
            text = this.lang[type];

        //замена плейсхолдеров
        if(type === 'max'){
            text = text.replace("(max)", this.compareCount[listKey][parent]);
            text = text.replace("(title)", elem.data('parent-title'));
        }
        //удаление или добавление
        if(type === 'add' || type === 'remove'){
            text = text.replace("(name)", this.compareOptions.listNames[listKey]);
        }

        var template = this.compareOptions.alertTemplate.replace("(text)", text);
        if(typeof elem.length !== 'undefined' && elem.length){
            elem.append(template);
        }
        setTimeout(function () {
            elem.find('.compare-message').remove();
        },1000);


    },
    /** Проверка наличия в списке **/
    check: function (id,parent,listKey) {
        var cookie = this.cookie[listKey];
       
        if(typeof cookie[parent] !== 'undefined' && cookie[parent] !== null && cookie[parent][id] === true){
            return true;
        }
        else{
            return false;
        }
    },
    /** обработчик клика по элементу **/
    toList: function (id,parent,listKey) {
        this.log("Клик по элементу списка "+listKey+", id = "+id+", parent = "+parent);
        var elem = $('.'+listKey+'-list[data-id="'+id+'"]');
        //убираем товар из спика
        if (this.check(id,parent,listKey)) {
            this.deleteFromCompare(elem,id,listKey);
        }
        else{
            this.addInCompare(elem,id, parent, listKey);
        }
        this.updateGlobalClass(listKey);

    },
    updateAll: function (params,list) {
        if (typeof params.unique === 'undefined') {
            params.unique = $('.compare-wrapper[data-list="'+list+'"] .compare-unique-value').data('status')
        }
        $.get(window.location,params,function (html) {
            var categoryHtml = $(html).find('.compare-wrapper[data-list="'+list+'"]').html();
            $('.compare-wrapper[data-list="'+list+'"]').html(categoryHtml)
        })

    },
    /** Установка обработчиков  **/
    setListener: function () {
        var obj = this;
        /** Обработчики событий **/
        $(document).on('click','.compare-remove-category',function (e) {
            e.preventDefault()
            var parent = $(this).data('id');
            var groupObj = $('.compare-group[data-id="'+parent+'"]')
            var list = groupObj.data('list');

            obj.deleteGroupFromCompare(parent,list);
            obj.updateAll({},list);
            obj.setActions(list)
        });

        $(document).on('click','.compare-remove-item',function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            var groupObj = $(this).closest('.compare-group');

            var list = groupObj.data('list');
            obj.deleteFromCompare({},id,list)

            obj.updateAll({},list);
            obj.setActions(list)


        });
        $(document).on('click','.compare-unique-value',function (e) {
            e.preventDefault();
            var parent = $(this).data('group');
            var status = parseInt($(this).data('status'));
            var list = $(this).closest('.compare-wrapper').data('list');
            if(status === 1)
                status = 0;
            else
                status = 1;
            obj.updateAll({unique:status},list);


        });
    },
    /** Вывод сообщений в консоль**/
    log:function (message,type ) {
        if(type !=="only"){
            message = "Compare js: "+message;
        }
        if(this.compareOptions.log === "1"){
            console.info(message);
        }
    },
    /** Стартовый метод **/
    run: function (option) {

        var obj = this;
        this.compareOptions  = $.extend(this.compareOptions,option);
        this.activeClass = option.activeClass;
        this.compareOptions.list.split(',').forEach(function (listKey) {
            obj.cookie[listKey] = obj.getCookie(listKey);
            obj.log("start set actions for list "+listKey);
            obj.setActions(listKey)
        });
        this.lang = option.lang;

        this.setListener()

    }
};
compare.run(compareOptions);
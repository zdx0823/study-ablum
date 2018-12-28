(function(){
    var del = document.getElementsByClassName("js-del");
    for (var i in del) {
        del[i].onclick = function(){
            return confirm("您确定要删除吗？");
        };
    }
})();
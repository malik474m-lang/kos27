<?php if (YANDEX_METRIKA_ID): ?>
<!-- Yandex.Metrika -->
<script type="text/javascript">
(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
m[i].l=1*new Date();
for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r)return;}
k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
(window,document,"script","https://mc.yandex.ru/metrika/tag.js","ym");
ym(<?= YANDEX_METRIKA_ID ?>, "init", {clickmap:true,trackLinks:true,accurateTrackBounce:true,webvisor:true});
window.kzTrackGoal=function(goal,params){
    try{if(typeof ym==='function')ym(<?= (int)YANDEX_METRIKA_ID ?>,'reachGoal',goal,params||{});}catch(e){}
    try{if(typeof gtag==='function')gtag('event',goal,params||{});}catch(e){}
};
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/<?= YANDEX_METRIKA_ID ?>" style="position:absolute;left:-9999px" alt=""></div></noscript>
<?php endif; ?>

<?php if (GOOGLE_ANALYTICS_ID): ?>
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= GOOGLE_ANALYTICS_ID ?>"></script>
<script>
window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}
gtag('js',new Date());gtag('config','<?= GOOGLE_ANALYTICS_ID ?>');
</script>
<?php endif; ?>

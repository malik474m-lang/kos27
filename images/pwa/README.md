# PWA Иконки

Для полноценной работы PWA нужно сгенерировать PNG иконки из `icon.svg`.

## Необходимые размеры:
- icon-72.png (72x72)
- icon-96.png (96x96)
- icon-128.png (128x128)
- icon-144.png (144x144)
- icon-152.png (152x152)
- icon-192.png (192x192)
- icon-384.png (384x384)
- icon-512.png (512x512)

## Способы генерации:

### Онлайн (проще всего):
1. Откройте https://realfavicongenerator.net/
2. Загрузите icon.svg (или ваш логотип)
3. Скачайте архив с иконками
4. Скопируйте файлы сюда

### Или используйте ImageMagick:
```bash
for size in 72 96 128 144 152 192 384 512; do
  convert icon.svg -resize ${size}x${size} icon-${size}.png
done
```

### Скриншоты (опционально):
- screenshot-wide.png (1280x720) — для десктопа
- screenshot-mobile.png (390x844) — для мобильных

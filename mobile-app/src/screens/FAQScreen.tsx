import React, { useState } from 'react';
import { View, Text, ScrollView, TouchableOpacity, StyleSheet } from 'react-native';
import { Colors } from '../constants/config';
import GradientHeader from '../components/GradientHeader';

const faqs = [
  { q: 'Что такое микрозайм?', a: 'Микрозайм — это небольшой краткосрочный заём от микрофинансовой организации (МФО). Суммы обычно от 1 000 до 100 000 рублей, сроки — от нескольких дней до нескольких месяцев.' },
  { q: 'Как оформить займ онлайн?', a: 'Выберите МФО на нашем сайте, нажмите «Оформить», заполните заявку на сайте партнёра. Потребуется паспорт и банковская карта. Решение обычно приходит за 5-15 минут.' },
  { q: 'Что значит «первый займ без процентов»?', a: 'Многие МФО предлагают первый займ под 0% при условии погашения в указанный срок (обычно 7-30 дней). Если вернёте вовремя — платите только сумму займа.' },
  { q: 'Что такое ПСК?', a: 'ПСК (Полная стоимость кредита) — все расходы заёмщика, выраженные в процентах годовых. Включает проценты, комиссии и обязательные платежи.' },
  { q: 'Могут ли отказать в займе?', a: 'Да, МФО может отказать. Частые причины: плохая кредитная история, наличие просрочек, несоответствие требованиям по возрасту или документам.' },
  { q: 'Как улучшить шансы на одобрение?', a: 'Подавайте заявку в несколько МФО, указывайте точные данные, начните с небольшой суммы, погасите текущие просрочки.' },
  { q: 'Безопасно ли оформлять займ онлайн?', a: 'Да, если МФО состоит в реестре ЦБ РФ. Проверяйте наличие лицензии на сайте Банка России. Все предложения в Космозайм — от проверенных партнёров.' },
  { q: 'Чем отличается займ от кредита?', a: 'Займы выдают МФО, суммы меньше, ставки выше, оформление быстрее. Кредиты выдают банки, суммы больше, ставки ниже, требования строже.' },
];

export default function FAQScreen() {
  const [openIndex, setOpenIndex] = useState<number | null>(null);

  return (
    <View style={styles.container}>
      <GradientHeader title="❓ Частые вопросы" subtitle="Ответы о займах и кредитах" />
      <ScrollView contentContainerStyle={styles.list} showsVerticalScrollIndicator={false}>
        {faqs.map((faq, i) => (
          <TouchableOpacity key={i} style={styles.item} onPress={() => setOpenIndex(openIndex === i ? null : i)} activeOpacity={0.7}>
            <View style={styles.questionRow}>
              <Text style={styles.question}>{faq.q}</Text>
              <Text style={styles.arrow}>{openIndex === i ? '▲' : '▼'}</Text>
            </View>
            {openIndex === i && <Text style={styles.answer}>{faq.a}</Text>}
          </TouchableOpacity>
        ))}
        <View style={{ height: 40 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.background },
  list: { padding: 16 },
  item: { backgroundColor: Colors.white, borderRadius: 14, borderWidth: 1, borderColor: Colors.surfaceBorder, padding: 16, marginBottom: 10 },
  questionRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  question: { fontSize: 15, fontWeight: '600', color: Colors.text, flex: 1, marginRight: 12 },
  arrow: { fontSize: 12, color: Colors.textMuted },
  answer: { fontSize: 14, color: Colors.textSecondary, lineHeight: 22, marginTop: 12, paddingTop: 12, borderTopWidth: 1, borderTopColor: Colors.borderLight },
});

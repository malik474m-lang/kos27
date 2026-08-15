import React, { useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  TextInput,
  Alert,
  StyleSheet,
  Linking,
} from 'react-native';
import { Colors, API_BASE_URL } from '../constants/config';
import { useAuth } from '../hooks/useAuth';
import { subscribe } from '../api/endpoints';

interface ProfileScreenProps {
  navigation: any;
}

export default function ProfileScreen({ navigation }: ProfileScreenProps) {
  const { isLoggedIn, userName, email, login, register, verify, logout, loading } = useAuth();

  // Формы
  const [mode, setMode] = useState<'login' | 'register' | 'verify'>('login');
  const [formEmail, setFormEmail] = useState('');
  const [formPassword, setFormPassword] = useState('');
  const [formName, setFormName] = useState('');
  const [formCode, setFormCode] = useState('');
  const [subEmail, setSubEmail] = useState('');
  const [subStatus, setSubStatus] = useState('');

  const handleLogin = async () => {
    try {
      await login(formEmail, formPassword);
      Alert.alert('Успешно', 'Вы вошли в аккаунт');
    } catch (e: any) {
      Alert.alert('Ошибка', e.message || 'Неверный email или пароль');
    }
  };

  const handleRegister = async () => {
    try {
      const message = await register(formEmail, formPassword, formName);
      Alert.alert('Код отправлен', message);
      setMode('verify');
    } catch (e: any) {
      Alert.alert('Ошибка', e.message || 'Не удалось зарегистрироваться');
    }
  };

  const handleVerify = async () => {
    try {
      await verify(formEmail, formCode);
      Alert.alert('Успешно', 'Аккаунт подтверждён');
    } catch (e: any) {
      Alert.alert('Ошибка', e.message || 'Неверный код');
    }
  };

  const handleSubscribe = async () => {
    try {
      const result = await subscribe(subEmail);
      setSubStatus(result.message);
      setSubEmail('');
    } catch (e: any) {
      setSubStatus(e.message || 'Ошибка подписки');
    }
  };

  if (isLoggedIn) {
    return (
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        <View style={styles.profileHeader}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{(userName || email)[0]?.toUpperCase()}</Text>
          </View>
          <Text style={styles.profileName}>{userName || 'Пользователь'}</Text>
          <Text style={styles.profileEmail}>{email}</Text>
        </View>

        <View style={styles.menuSection}>
          <TouchableOpacity style={styles.menuItem} onPress={() => navigation.navigate('Favorites')}>
            <Text style={styles.menuIcon}>❤️</Text>
            <Text style={styles.menuLabel}>Избранное</Text>
            <Text style={styles.menuArrow}>→</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.menuItem} onPress={() => navigation.navigate('Calculator')}>
            <Text style={styles.menuIcon}>🧮</Text>
            <Text style={styles.menuLabel}>Калькулятор</Text>
            <Text style={styles.menuArrow}>→</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.menuItem} onPress={() => navigation.navigate('Articles')}>
            <Text style={styles.menuIcon}>📰</Text>
            <Text style={styles.menuLabel}>Статьи</Text>
            <Text style={styles.menuArrow}>→</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.menuItem} onPress={() => Linking.openURL(`${API_BASE_URL}/cabinet`)}>
            <Text style={styles.menuIcon}>📋</Text>
            <Text style={styles.menuLabel}>Мои заявки (на сайте)</Text>
            <Text style={styles.menuArrow}>→</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.menuItem} onPress={() => Linking.openURL(`${API_BASE_URL}/contact`)}>
            <Text style={styles.menuIcon}>✉️</Text>
            <Text style={styles.menuLabel}>Связаться с нами</Text>
            <Text style={styles.menuArrow}>→</Text>
          </TouchableOpacity>
        </View>

        {/* Подписка на рассылку */}
        <View style={styles.subscribeCard}>
          <Text style={styles.subscribeTitle}>📬 Подписка на рассылку</Text>
          <Text style={styles.subscribeText}>Получайте лучшие предложения на email</Text>
          <View style={styles.subscribeRow}>
            <TextInput
              style={styles.subscribeInput}
              value={subEmail}
              onChangeText={setSubEmail}
              placeholder="Ваш email"
              keyboardType="email-address"
              autoCapitalize="none"
              placeholderTextColor={Colors.textMuted}
            />
            <TouchableOpacity style={styles.subscribeBtn} onPress={handleSubscribe}>
              <Text style={styles.subscribeBtnText}>→</Text>
            </TouchableOpacity>
          </View>
          {subStatus ? <Text style={styles.subscribeStatus}>{subStatus}</Text> : null}
        </View>

        {/* Выход */}
        <TouchableOpacity style={styles.logoutBtn} onPress={logout}>
          <Text style={styles.logoutBtnText}>Выйти из аккаунта</Text>
        </TouchableOpacity>

        {/* Правовая информация */}
        <View style={styles.legalSection}>
          <TouchableOpacity onPress={() => Linking.openURL(`${API_BASE_URL}/privacy`)}>
            <Text style={styles.legalLink}>Политика конфиденциальности</Text>
          </TouchableOpacity>
          <TouchableOpacity onPress={() => Linking.openURL(`${API_BASE_URL}/terms`)}>
            <Text style={styles.legalLink}>Пользовательское соглашение</Text>
          </TouchableOpacity>
          <TouchableOpacity onPress={() => Linking.openURL(`${API_BASE_URL}/disclaimer`)}>
            <Text style={styles.legalLink}>Дисклеймер</Text>
          </TouchableOpacity>
          <Text style={styles.versionText}>Космозайм v1.0.0</Text>
        </View>

        <View style={{ height: 40 }} />
      </ScrollView>
    );
  }

  // Неавторизованный — формы входа/регистрации
  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.authContainer} showsVerticalScrollIndicator={false}>
      <View style={styles.authCard}>
        <Text style={styles.authTitle}>
          {mode === 'login' ? '👤 Вход' : mode === 'register' ? '📝 Регистрация' : '🔑 Код подтверждения'}
        </Text>

        {mode === 'verify' ? (
          <>
            <Text style={styles.verifyHint}>Код отправлен на {formEmail}</Text>
            <TextInput
              style={styles.authInput}
              value={formCode}
              onChangeText={setFormCode}
              placeholder="6-значный код"
              keyboardType="number-pad"
              placeholderTextColor={Colors.textMuted}
            />
            <TouchableOpacity style={styles.authBtn} onPress={handleVerify} disabled={loading}>
              <Text style={styles.authBtnText}>{loading ? 'Проверка...' : 'Подтвердить'}</Text>
            </TouchableOpacity>
          </>
        ) : (
          <>
            {mode === 'register' && (
              <TextInput
                style={styles.authInput}
                value={formName}
                onChangeText={setFormName}
                placeholder="Ваше имя"
                placeholderTextColor={Colors.textMuted}
              />
            )}
            <TextInput
              style={styles.authInput}
              value={formEmail}
              onChangeText={setFormEmail}
              placeholder="Email"
              keyboardType="email-address"
              autoCapitalize="none"
              placeholderTextColor={Colors.textMuted}
            />
            <TextInput
              style={styles.authInput}
              value={formPassword}
              onChangeText={setFormPassword}
              placeholder="Пароль"
              secureTextEntry
              placeholderTextColor={Colors.textMuted}
            />
            <TouchableOpacity
              style={styles.authBtn}
              onPress={mode === 'login' ? handleLogin : handleRegister}
              disabled={loading}
            >
              <Text style={styles.authBtnText}>
                {loading ? 'Загрузка...' : mode === 'login' ? 'Войти' : 'Зарегистрироваться'}
              </Text>
            </TouchableOpacity>
          </>
        )}

        <View style={styles.authSwitch}>
          {mode === 'login' ? (
            <TouchableOpacity onPress={() => setMode('register')}>
              <Text style={styles.authSwitchText}>Нет аккаунта? <Text style={styles.authSwitchLink}>Зарегистрироваться</Text></Text>
            </TouchableOpacity>
          ) : (
            <TouchableOpacity onPress={() => setMode('login')}>
              <Text style={styles.authSwitchText}>Уже есть аккаунт? <Text style={styles.authSwitchLink}>Войти</Text></Text>
            </TouchableOpacity>
          )}
        </View>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.background },
  profileHeader: { alignItems: 'center', paddingTop: 60, paddingBottom: 24, backgroundColor: Colors.primary },
  avatar: { width: 80, height: 80, borderRadius: 40, backgroundColor: 'rgba(255,255,255,0.2)', alignItems: 'center', justifyContent: 'center' },
  avatarText: { fontSize: 32, fontWeight: '700', color: Colors.white },
  profileName: { fontSize: 22, fontWeight: '700', color: Colors.white, marginTop: 12 },
  profileEmail: { fontSize: 14, color: 'rgba(255,255,255,0.7)', marginTop: 4 },
  menuSection: { backgroundColor: Colors.white, margin: 16, borderRadius: 16, borderWidth: 1, borderColor: Colors.surfaceBorder, overflow: 'hidden' },
  menuItem: { flexDirection: 'row', alignItems: 'center', padding: 16, borderBottomWidth: 1, borderBottomColor: Colors.borderLight },
  menuIcon: { fontSize: 20, width: 36 },
  menuLabel: { flex: 1, fontSize: 15, fontWeight: '500', color: Colors.text },
  menuArrow: { fontSize: 16, color: Colors.textMuted },
  subscribeCard: { backgroundColor: Colors.white, margin: 16, marginTop: 0, borderRadius: 16, padding: 20, borderWidth: 1, borderColor: Colors.surfaceBorder },
  subscribeTitle: { fontSize: 17, fontWeight: '700', color: Colors.text, marginBottom: 4 },
  subscribeText: { fontSize: 14, color: Colors.textSecondary, marginBottom: 12 },
  subscribeRow: { flexDirection: 'row', gap: 8 },
  subscribeInput: { flex: 1, backgroundColor: Colors.background, borderWidth: 1, borderColor: Colors.border, borderRadius: 10, paddingHorizontal: 14, paddingVertical: 10, fontSize: 14, color: Colors.text },
  subscribeBtn: { backgroundColor: Colors.primary, width: 48, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  subscribeBtnText: { color: Colors.white, fontSize: 18, fontWeight: '700' },
  subscribeStatus: { fontSize: 13, color: Colors.accent, marginTop: 8 },
  logoutBtn: { marginHorizontal: 16, marginTop: 8, backgroundColor: Colors.white, borderRadius: 12, padding: 16, alignItems: 'center', borderWidth: 1, borderColor: Colors.error + '30' },
  logoutBtnText: { color: Colors.error, fontSize: 15, fontWeight: '600' },
  legalSection: { padding: 20, alignItems: 'center', gap: 12 },
  legalLink: { fontSize: 13, color: Colors.primary, textDecorationLine: 'underline' },
  versionText: { fontSize: 12, color: Colors.textMuted, marginTop: 8 },
  authContainer: { justifyContent: 'center', paddingVertical: 60 },
  authCard: { backgroundColor: Colors.white, margin: 24, borderRadius: 20, padding: 28, borderWidth: 1, borderColor: Colors.surfaceBorder },
  authTitle: { fontSize: 24, fontWeight: '700', color: Colors.text, marginBottom: 24, textAlign: 'center' },
  verifyHint: { fontSize: 14, color: Colors.textSecondary, marginBottom: 16, textAlign: 'center' },
  authInput: { backgroundColor: Colors.background, borderWidth: 1, borderColor: Colors.border, borderRadius: 12, paddingHorizontal: 16, paddingVertical: 14, fontSize: 15, color: Colors.text, marginBottom: 12 },
  authBtn: { backgroundColor: Colors.primary, borderRadius: 12, paddingVertical: 16, alignItems: 'center', marginTop: 4 },
  authBtnText: { color: Colors.white, fontSize: 16, fontWeight: '700' },
  authSwitch: { marginTop: 20, alignItems: 'center' },
  authSwitchText: { fontSize: 14, color: Colors.textSecondary },
  authSwitchLink: { color: Colors.primary, fontWeight: '600' },
});

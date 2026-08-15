import React from 'react';
import { StatusBar } from 'expo-status-bar';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Text, Platform } from 'react-native';
import { Colors } from './src/constants/config';
import { AuthContext, useAuthProvider } from './src/hooks/useAuth';

import HomeScreen from './src/screens/HomeScreen';
import CatalogScreen from './src/screens/CatalogScreen';
import OfferDetailScreen from './src/screens/OfferDetailScreen';
import CalculatorScreen from './src/screens/CalculatorScreen';
import ArticlesScreen from './src/screens/ArticlesScreen';
import FavoritesScreen from './src/screens/FavoritesScreen';
import ProfileScreen from './src/screens/ProfileScreen';

const Stack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();

function TabIcon({ name, focused }: { name: string; focused: boolean }) {
  const icons: Record<string, string> = {
    Home: '🏠', Catalog: '📋', Calculator: '🧮', Articles: '📰', Profile: '👤',
  };
  return <Text style={{ fontSize: focused ? 26 : 22, opacity: focused ? 1 : 0.5 }}>{icons[name] || '📌'}</Text>;
}

function HomeTabs() {
  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        headerShown: false,
        tabBarIcon: ({ focused }) => <TabIcon name={route.name} focused={focused} />,
        tabBarActiveTintColor: Colors.primary,
        tabBarInactiveTintColor: Colors.textMuted,
        tabBarStyle: { backgroundColor: Colors.white, borderTopColor: Colors.surfaceBorder, paddingTop: 4, height: Platform.OS === 'ios' ? 88 : 64 },
        tabBarLabelStyle: { fontSize: 11, fontWeight: '600' as const },
      })}
    >
      <Tab.Screen name="Home" component={HomeScreen} options={{ title: 'Главная' }} />
      <Tab.Screen name="Catalog" component={CatalogScreen} options={{ title: 'Каталог' }} initialParams={{ category: 'microloans' }} />
      <Tab.Screen name="Calculator" component={CalculatorScreen} options={{ title: 'Калькулятор' }} />
      <Tab.Screen name="Articles" component={ArticlesScreen} options={{ title: 'Статьи' }} />
      <Tab.Screen name="Profile" component={ProfileScreen} options={{ title: 'Профиль' }} />
    </Tab.Navigator>
  );
}

export default function App() {
  const auth = useAuthProvider();
  return (
    <AuthContext.Provider value={auth}>
      <NavigationContainer>
        <StatusBar style="auto" />
        <Stack.Navigator
          screenOptions={{
            headerStyle: { backgroundColor: Colors.white },
            headerTintColor: Colors.text,
            headerTitleStyle: { fontWeight: '700' },
            headerBackTitle: 'Назад',
          }}
        >
          <Stack.Screen name="MainTabs" component={HomeTabs} options={{ headerShown: false }} />
          <Stack.Screen
            name="OfferDetail"
            component={OfferDetailScreen}
            options={({ route }: any) => ({ title: route.params?.offer?.title || 'Предложение' })}
          />
          <Stack.Screen name="Favorites" component={FavoritesScreen} options={{ title: 'Избранное', headerShown: false }} />
        </Stack.Navigator>
      </NavigationContainer>
    </AuthContext.Provider>
  );
}

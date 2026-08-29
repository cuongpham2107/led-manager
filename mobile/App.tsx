import { StatusBar } from 'expo-status-bar';
import React, { useState } from 'react';
import { ActivityIndicator, StyleSheet, View } from 'react-native';
import { AuthProvider, useAuth } from './src/context/AuthContext';
import { AssetLookupScreen } from './src/screens/AssetLookupScreen';
import { CheckinBatchesScreen } from './src/screens/CheckinBatchesScreen';
import { CheckinDetailScreen } from './src/screens/CheckinDetailScreen';
import { CheckoutBatchesScreen } from './src/screens/CheckoutBatchesScreen';
import { CheckoutDetailScreen } from './src/screens/CheckoutDetailScreen';
import { HomeScreen } from './src/screens/HomeScreen';
import { LoginScreen } from './src/screens/LoginScreen';
import { ReturnBatchesScreen } from './src/screens/ReturnBatchesScreen';
import { ReturnDetailScreen } from './src/screens/ReturnDetailScreen';
import { CheckinBatch, CheckoutBatch, ReturnBatch } from './src/types';

type ScreenName =
  | 'home'
  | 'checkout_batches'
  | 'checkout_detail'
  | 'return_batches'
  | 'return_detail'
  | 'checkin_batches'
  | 'checkin_detail'
  | 'asset_lookup';

const MainAppContent: React.FC = () => {
  const { token, isLoading } = useAuth();
  const [currentScreen, setCurrentScreen] = useState<ScreenName>('home');
  const [selectedCheckoutBatchId, setSelectedCheckoutBatchId] = useState<number | null>(null);
  const [selectedReturnBatchId, setSelectedReturnBatchId] = useState<number | null>(null);
  const [selectedCheckinBatchId, setSelectedCheckinBatchId] = useState<number | null>(null);

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator color="#2563EB" size="large" />
      </View>
    );
  }

  if (!token) {
    return <LoginScreen />;
  }

  const navigateTo = (screen: ScreenName) => {
    setCurrentScreen(screen);
  };

  const handleSelectCheckoutBatch = (batch: CheckoutBatch) => {
    setSelectedCheckoutBatchId(batch.id);
    setCurrentScreen('checkout_detail');
  };

  const handleSelectReturnBatch = (batch: ReturnBatch) => {
    setSelectedReturnBatchId(batch.id);
    setCurrentScreen('return_detail');
  };

  const handleSelectCheckinBatch = (batch: CheckinBatch) => {
    setSelectedCheckinBatchId(batch.id);
    setCurrentScreen('checkin_detail');
  };

  switch (currentScreen) {
    case 'checkout_batches':
      return (
        <CheckoutBatchesScreen
          onBack={() => navigateTo('home')}
          onSelectBatch={handleSelectCheckoutBatch}
        />
      );

    case 'checkout_detail':
      return selectedCheckoutBatchId ? (
        <CheckoutDetailScreen
          batchId={selectedCheckoutBatchId}
          onBack={() => navigateTo('checkout_batches')}
        />
      ) : (
        <HomeScreen onNavigate={(s) => navigateTo(s as ScreenName)} />
      );

    case 'return_batches':
      return (
        <ReturnBatchesScreen
          onBack={() => navigateTo('home')}
          onSelectBatch={handleSelectReturnBatch}
        />
      );

    case 'return_detail':
      return selectedReturnBatchId ? (
        <ReturnDetailScreen
          batchId={selectedReturnBatchId}
          onBack={() => navigateTo('return_batches')}
        />
      ) : (
        <HomeScreen onNavigate={(s) => navigateTo(s as ScreenName)} />
      );

    case 'checkin_batches':
      return (
        <CheckinBatchesScreen
          onBack={() => navigateTo('home')}
          onSelectBatch={handleSelectCheckinBatch}
        />
      );

    case 'checkin_detail':
      return selectedCheckinBatchId ? (
        <CheckinDetailScreen
          batchId={selectedCheckinBatchId}
          onBack={() => navigateTo('checkin_batches')}
        />
      ) : (
        <HomeScreen onNavigate={(s) => navigateTo(s as ScreenName)} />
      );

    case 'asset_lookup':
      return <AssetLookupScreen onBack={() => navigateTo('home')} />;

    case 'home':
    default:
      return <HomeScreen onNavigate={(s) => navigateTo(s as ScreenName)} />;
  }
};

export default function App() {
  return (
    <AuthProvider>
      <View style={styles.appRoot}>
        <StatusBar style="dark" />
        <MainAppContent />
      </View>
    </AuthProvider>
  );
}

const styles = StyleSheet.create({
  appRoot: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  loadingContainer: {
    flex: 1,
    backgroundColor: '#F8FAFC',
    alignItems: 'center',
    justifyContent: 'center',
  },
});

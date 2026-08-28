import { KeyRound, Lock, Mail, QrCode, Server } from 'lucide-react-native';
import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Platform,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { DEFAULT_API_HOST, getApiBaseUrl, setApiBaseUrl } from '../config/api';
import { useAuth } from '../context/AuthContext';

export const LoginScreen: React.FC = () => {
  const { login } = useAuth();
  const [email, setEmail] = useState<string>('khohn@ledmanager.com');
  const [password, setPassword] = useState<string>('password');
  const [serverUrl, setServerUrl] = useState<string>(DEFAULT_API_HOST);
  const [showServerConfig, setShowServerConfig] = useState<boolean>(false);
  const [isLoading, setIsLoading] = useState<boolean>(false);

  useEffect(() => {
    getApiBaseUrl().then(setServerUrl);
  }, []);

  const handleLogin = async () => {
    if (!email.trim() || !password.trim()) {
      Alert.alert('Lỗi', 'Vui lòng nhập đầy đủ Email và Mật khẩu.');
      return;
    }

    setIsLoading(true);
    try {
      if (serverUrl.trim()) {
        await setApiBaseUrl(serverUrl.trim());
      }
      await login(email.trim(), password);
    } catch (err: any) {
      const msg = err?.response?.data?.message || err?.message || 'Đăng nhập không thành công.';
      Alert.alert('Đăng nhập thất bại', msg);
    } finally {
      setIsLoading(false);
    }
  };

  const quickFillAccount = (userEmail: string) => {
    setEmail(userEmail);
    setPassword('password');
  };

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={{ flex: 1 }}
      >
        <ScrollView contentContainerStyle={styles.scrollContent} keyboardShouldPersistTaps="handled">
          {/* Brand Logo Header */}
          <View style={styles.logoSection}>
            <View style={styles.logoBadge}>
              <QrCode color="#2563EB" size={44} />
            </View>
            <Text style={styles.appTitle}>LED OS Kho & Sự Kiện</Text>
            <Text style={styles.appSubtitle}>Hệ thống quét mã QR xuất nhập kho & quản lý thiết bị</Text>
          </View>

          {/* Login Card */}
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Đăng nhập nhân sự</Text>

            {/* Email Field */}
            <View style={styles.inputGroup}>
              <Text style={styles.label}>Email tài khoản</Text>
              <View style={styles.inputWrap}>
                <Mail color="#64748B" size={20} style={styles.inputIcon} />
                <TextInput
                  style={styles.input}
                  placeholder="name@ledmanager.com"
                  placeholderTextColor="#94A3B8"
                  value={email}
                  onChangeText={setEmail}
                  autoCapitalize="none"
                  keyboardType="email-address"
                />
              </View>
            </View>

            {/* Password Field */}
            <View style={styles.inputGroup}>
              <Text style={styles.label}>Mật khẩu</Text>
              <View style={styles.inputWrap}>
                <Lock color="#64748B" size={20} style={styles.inputIcon} />
                <TextInput
                  style={styles.input}
                  placeholder="••••••••"
                  placeholderTextColor="#94A3B8"
                  value={password}
                  onChangeText={setPassword}
                  secureTextEntry
                />
              </View>
            </View>

            {/* Submit Button */}
            <TouchableOpacity
              style={[styles.loginBtn, isLoading && styles.loginBtnDisabled]}
              onPress={handleLogin}
              disabled={isLoading}
            >
              {isLoading ? (
                <ActivityIndicator color="#fff" size="small" />
              ) : (
                <Text style={styles.loginBtnText}>Đăng Nhập Hệ Thống</Text>
              )}
            </TouchableOpacity>

            {/* Quick-fill Demo Accounts */}
            <View style={styles.quickFillSection}>
              <Text style={styles.quickFillTitle}>Chọn nhanh tài khoản nhân sự:</Text>
              <View style={styles.quickFillRow}>
                <TouchableOpacity
                  style={styles.quickFillBtn}
                  onPress={() => quickFillAccount('khohn@ledmanager.com')}
                >
                  <Text style={styles.quickFillBtnText}>Kho HN</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={styles.quickFillBtn}
                  onPress={() => quickFillAccount('khohcm@ledmanager.com')}
                >
                  <Text style={styles.quickFillBtnText}>Kho HCM</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={styles.quickFillBtn}
                  onPress={() => quickFillAccount('tech@ledmanager.com')}
                >
                  <Text style={styles.quickFillBtnText}>Kỹ Thuật</Text>
                </TouchableOpacity>
              </View>
            </View>

            {/* Server IP Config Toggle */}
            <TouchableOpacity
              style={styles.serverToggle}
              onPress={() => setShowServerConfig((prev) => !prev)}
            >
              <Server color="#64748B" size={16} />
              <Text style={styles.serverToggleText}>
                {showServerConfig ? 'Ẩn cấu hình máy chủ API' : 'Cấu hình địa chỉ máy chủ API'}
              </Text>
            </TouchableOpacity>

            {showServerConfig && (
              <View style={styles.serverBox}>
                <Text style={styles.serverLabel}>API Host URL:</Text>
                <TextInput
                  style={styles.serverInput}
                  value={serverUrl}
                  onChangeText={setServerUrl}
                  autoCapitalize="none"
                  placeholder="http://192.168.1.xxx:8000"
                />
                <Text style={styles.serverHint}>
                  (Khi test trên điện thoại thật cùng mạng Wifi, nhập IP máy tính của bạn e.g. http://192.168.1.15:8000)
                </Text>
              </View>
            )}
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F1F5F9',
  },
  scrollContent: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 24,
  },
  logoSection: {
    alignItems: 'center',
    marginBottom: 28,
  },
  logoBadge: {
    width: 80,
    height: 80,
    borderRadius: 24,
    backgroundColor: '#EFF6FF',
    borderWidth: 1,
    borderColor: '#BFDBFE',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
    shadowColor: '#2563EB',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 10,
  },
  appTitle: {
    color: '#0F172A',
    fontSize: 24,
    fontWeight: '800',
    letterSpacing: 0.3,
  },
  appSubtitle: {
    color: '#64748B',
    fontSize: 13,
    marginTop: 6,
    textAlign: 'center',
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    padding: 24,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#64748B',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.1,
    shadowRadius: 16,
    elevation: 4,
  },
  cardTitle: {
    color: '#0F172A',
    fontSize: 18,
    fontWeight: '700',
    marginBottom: 20,
  },
  inputGroup: {
    marginBottom: 18,
  },
  label: {
    color: '#334155',
    fontSize: 13,
    fontWeight: '600',
    marginBottom: 8,
  },
  inputWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#CBD5E1',
    paddingHorizontal: 14,
  },
  inputIcon: {
    marginRight: 10,
  },
  input: {
    flex: 1,
    height: 48,
    color: '#0F172A',
    fontSize: 15,
  },
  loginBtn: {
    height: 52,
    backgroundColor: '#2563EB',
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 8,
    shadowColor: '#2563EB',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 3,
  },
  loginBtnDisabled: {
    backgroundColor: '#93C5FD',
  },
  loginBtnText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '700',
  },
  quickFillSection: {
    marginTop: 20,
    paddingTop: 16,
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
  },
  quickFillTitle: {
    color: '#64748B',
    fontSize: 12,
    marginBottom: 8,
  },
  quickFillRow: {
    flexDirection: 'row',
    gap: 8,
  },
  quickFillBtn: {
    flex: 1,
    backgroundColor: '#EFF6FF',
    paddingVertical: 9,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#DBEAFE',
    alignItems: 'center',
  },
  quickFillBtnText: {
    color: '#2563EB',
    fontSize: 12,
    fontWeight: '700',
  },
  serverToggle: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 20,
    paddingVertical: 8,
  },
  serverToggleText: {
    color: '#64748B',
    fontSize: 12,
    marginLeft: 6,
  },
  serverBox: {
    backgroundColor: '#F8FAFC',
    padding: 12,
    borderRadius: 10,
    marginTop: 8,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  serverLabel: {
    color: '#64748B',
    fontSize: 11,
    marginBottom: 4,
  },
  serverInput: {
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    paddingHorizontal: 10,
    height: 38,
    color: '#0F172A',
    fontSize: 13,
    borderWidth: 1,
    borderColor: '#CBD5E1',
  },
  serverHint: {
    color: '#94A3B8',
    fontSize: 11,
    marginTop: 4,
  },
});

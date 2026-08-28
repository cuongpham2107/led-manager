import {
  ArrowDownLeft,
  ArrowUpRight,
  Boxes,
  Building2,
  ChevronRight,
  LogOut,
  MapPin,
  QrCode,
  Search,
  User as UserIcon,
} from 'lucide-react-native';
import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  RefreshControl,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { useAuth } from '../context/AuthContext';
import { apiClient } from '../services/apiClient';

interface HomeScreenProps {
  onNavigate: (screen: string, params?: any) => void;
}

export const HomeScreen: React.FC<HomeScreenProps> = ({ onNavigate }) => {
  const { user, logout, selectedWarehouseId } = useAuth();
  const [stats, setStats] = useState<{
    pendingOutbound: number;
    pendingReturns: number;
  }>({
    pendingOutbound: 0,
    pendingReturns: 0,
  });
  const [isRefreshing, setIsRefreshing] = useState<boolean>(false);

  const warehouseName = user?.warehouse?.name || 'Kho chính';
  const warehouseCode = user?.warehouse?.code || 'WH';

  const fetchDashboardStats = async () => {
    try {
      const [outboundRes, returnRes] = await Promise.all([
        apiClient.get('/checkout-batches', {
          params: { warehouse_id: selectedWarehouseId, status: 'pending' },
        }),
        apiClient.get('/return-batches', {
          params: { warehouse_id: selectedWarehouseId, status: 'pending' },
        }),
      ]);

      setStats({
        pendingOutbound: outboundRes.data?.pagination?.total ?? 0,
        pendingReturns: returnRes.data?.pagination?.total ?? 0,
      });
    } catch {
      // Ignore
    }
  };

  useEffect(() => {
    fetchDashboardStats();
  }, [selectedWarehouseId]);

  const onRefresh = async () => {
    setIsRefreshing(true);
    await fetchDashboardStats();
    setIsRefreshing(false);
  };

  const handleLogout = () => {
    Alert.alert(
      'Đăng xuất',
      'Bạn có chắc chắn muốn đăng xuất khỏi ứng dụng?',
      [
        { text: 'Hủy', style: 'cancel' },
        { text: 'Đăng xuất', style: 'destructive', onPress: logout },
      ]
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* Top App Header */}
      <View style={styles.header}>
        <View style={styles.userInfo}>
          <Text style={styles.greeting}>Xin chào,</Text>
          <Text style={styles.userName}>{user?.name ?? 'Nhân sự kho'}</Text>
          <View style={styles.warehouseChip}>
            <MapPin color="#2563EB" size={13} style={{ marginRight: 4 }} />
            <Text style={styles.warehouseChipText}>
              {warehouseName} ({warehouseCode})
            </Text>
          </View>
        </View>
        <TouchableOpacity onPress={handleLogout} style={styles.logoutBtn}>
          <LogOut color="#DC2626" size={20} />
        </TouchableOpacity>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        refreshControl={
          <RefreshControl
            refreshing={isRefreshing}
            onRefresh={onRefresh}
            tintColor="#2563EB"
          />
        }
      >
        {/* Quick Action Big Tiles */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Nghiệp Vụ Kho & Quét QR</Text>

          {/* 1. Outbound / Checkout Scan */}
          <TouchableOpacity
            style={[styles.actionCard, { borderLeftColor: '#2563EB' }]}
            onPress={() => onNavigate('checkout_batches')}
          >
            <View style={[styles.actionIconBox, { backgroundColor: '#EFF6FF' }]}>
              <ArrowUpRight color="#2563EB" size={26} />
            </View>
            <View style={styles.actionTextBox}>
              <View style={styles.actionTitleRow}>
                <Text style={styles.actionTitle}>Xuất Kho Đi Sự Kiện</Text>
                {stats.pendingOutbound > 0 && (
                  <View style={styles.badgeCount}>
                    <Text style={styles.badgeCountText}>{stats.pendingOutbound} đợt</Text>
                  </View>
                )}
              </View>
              <Text style={styles.actionDesc}>
                Bắn mã QR quét kiểm tra & phân bổ Cabinet LED xuất đi sân khấu sự kiện
              </Text>
            </View>
            <ChevronRight color="#94A3B8" size={20} />
          </TouchableOpacity>

          {/* 2. Inbound / Return & Grading */}
          <TouchableOpacity
            style={[styles.actionCard, { borderLeftColor: '#059669' }]}
            onPress={() => onNavigate('return_batches')}
          >
            <View style={[styles.actionIconBox, { backgroundColor: '#ECFDF5' }]}>
              <ArrowDownLeft color="#059669" size={26} />
            </View>
            <View style={styles.actionTextBox}>
              <View style={styles.actionTitleRow}>
                <Text style={styles.actionTitle}>Thu Hồi & Nhập Trả Kho</Text>
                {stats.pendingReturns > 0 && (
                  <View style={[styles.badgeCount, { backgroundColor: '#FEF3C7' }]}>
                    <Text style={[styles.badgeCountText, { color: '#B45309' }]}>
                      {stats.pendingReturns} đợt
                    </Text>
                  </View>
                )}
              </View>
              <Text style={styles.actionDesc}>
                Quét mã thu hồi, chấm điểm chất lượng (Grading) & tự động tạo phiếu sửa chữa
              </Text>
            </View>
            <ChevronRight color="#94A3B8" size={20} />
          </TouchableOpacity>

          {/* 3. Instant Asset Lookup */}
          <TouchableOpacity
            style={[styles.actionCard, { borderLeftColor: '#7C3AED' }]}
            onPress={() => onNavigate('asset_lookup')}
          >
            <View style={[styles.actionIconBox, { backgroundColor: '#F5F3FF' }]}>
              <Search color="#7C3AED" size={26} />
            </View>
            <View style={styles.actionTextBox}>
              <Text style={styles.actionTitle}>Quét Tra Cứu Thiết Bị</Text>
              <Text style={styles.actionDesc}>
                Bắn mã QR trên Cabinet / Flycase để xem nguồn gốc, thông số & lịch sử bảo dưỡng
              </Text>
            </View>
            <ChevronRight color="#94A3B8" size={20} />
          </TouchableOpacity>
        </View>

        {/* Real-time Tips Box */}
        <View style={styles.tipsBox}>
          <QrCode color="#2563EB" size={24} style={{ marginRight: 12 }} />
          <View style={{ flex: 1 }}>
            <Text style={styles.tipsTitle}>Mẹo quét nhanh:</Text>
            <Text style={styles.tipsText}>
              Bật chế độ "Quét liên tục" để bắn nhanh hàng chục mã QR cabin LED liên tiếp mà không cần bấm xác nhận từng lần.
            </Text>
          </View>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 18,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  userInfo: {
    flex: 1,
  },
  greeting: {
    color: '#64748B',
    fontSize: 13,
  },
  userName: {
    color: '#0F172A',
    fontSize: 20,
    fontWeight: '800',
    marginTop: 2,
  },
  warehouseChip: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#EFF6FF',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
    alignSelf: 'flex-start',
    marginTop: 6,
    borderWidth: 1,
    borderColor: '#DBEAFE',
  },
  warehouseChipText: {
    color: '#1D4ED8',
    fontSize: 12,
    fontWeight: '700',
  },
  logoutBtn: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: '#FEF2F2',
    borderWidth: 1,
    borderColor: '#FEE2E2',
    alignItems: 'center',
    justifyContent: 'center',
  },
  scrollContent: {
    padding: 16,
  },
  section: {
    marginBottom: 20,
  },
  sectionTitle: {
    color: '#334155',
    fontSize: 15,
    fontWeight: '800',
    marginBottom: 12,
    letterSpacing: 0.2,
  },
  actionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderLeftWidth: 5,
    shadowColor: '#64748B',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.08,
    shadowRadius: 8,
    elevation: 2,
  },
  actionIconBox: {
    width: 52,
    height: 52,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 14,
  },
  actionTextBox: {
    flex: 1,
  },
  actionTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  actionTitle: {
    color: '#0F172A',
    fontSize: 16,
    fontWeight: '700',
  },
  actionDesc: {
    color: '#64748B',
    fontSize: 12,
    marginTop: 4,
    lineHeight: 16,
  },
  badgeCount: {
    backgroundColor: '#DBEAFE',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 12,
    marginLeft: 8,
  },
  badgeCountText: {
    color: '#1D4ED8',
    fontSize: 11,
    fontWeight: '700',
  },
  tipsBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#EFF6FF',
    borderRadius: 14,
    padding: 16,
    borderWidth: 1,
    borderColor: '#BFDBFE',
  },
  tipsTitle: {
    color: '#1D4ED8',
    fontSize: 13,
    fontWeight: '700',
    marginBottom: 2,
  },
  tipsText: {
    color: '#475569',
    fontSize: 12,
    lineHeight: 18,
  },
});

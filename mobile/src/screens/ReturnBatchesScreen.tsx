import { ArrowDownLeft, ArrowLeft, Calendar, ChevronRight, Search, Wrench } from 'lucide-react-native';
import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  RefreshControl,
  SafeAreaView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { StatusBadge } from '../components/StatusBadge';
import { useAuth } from '../context/AuthContext';
import { apiClient } from '../services/apiClient';
import { ReturnBatch } from '../types';

interface ReturnBatchesScreenProps {
  onBack: () => void;
  onSelectBatch: (batch: ReturnBatch) => void;
}

export const ReturnBatchesScreen: React.FC<ReturnBatchesScreenProps> = ({
  onBack,
  onSelectBatch,
}) => {
  const { selectedWarehouseId } = useAuth();
  const [batches, setBatches] = useState<ReturnBatch[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [isRefreshing, setIsRefreshing] = useState<boolean>(false);
  const [searchTerm, setSearchTerm] = useState<string>('');

  const fetchBatches = async () => {
    try {
      const response = await apiClient.get('/return-batches', {
        params: {
          warehouse_id: selectedWarehouseId,
          search: searchTerm.trim() || undefined,
        },
      });
      if (response.data?.success) {
        setBatches(response.data.data);
      }
    } catch {
      // Ignore
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  };

  useEffect(() => {
    fetchBatches();
  }, [selectedWarehouseId, searchTerm]);

  const onRefresh = () => {
    setIsRefreshing(true);
    fetchBatches();
  };

  const renderItem = ({ item }: { item: ReturnBatch }) => (
    <TouchableOpacity style={styles.card} onPress={() => onSelectBatch(item)}>
      <View style={styles.cardHeader}>
        <View style={styles.codeRow}>
          <Text style={styles.batchCode}>{item.code}</Text>
          <StatusBadge label={item.status.label} color={item.status.color} size="sm" />
        </View>
        <ChevronRight color="#94A3B8" size={20} />
      </View>

      <Text style={styles.eventName}>
        {item.checkout_batch?.order?.event || item.checkout_batch?.order?.order_no || 'Đợt thu hồi'}
      </Text>
      <Text style={styles.customerName}>
        Khách hàng: {item.checkout_batch?.customer?.name || 'N/A'}
      </Text>

      {/* Grading Stat Summary */}
      <View style={styles.gradingStatsRow}>
        <View style={[styles.statBadge, { backgroundColor: '#DCFCE7', borderColor: '#86EFAC' }]}>
          <Text style={[styles.statText, { color: '#15803D' }]}>
            ✓ Đạt chuẩn: {item.normal_count}
          </Text>
        </View>
        {item.damaged_count > 0 && (
          <View style={[styles.statBadge, { backgroundColor: '#FEE2E2', borderColor: '#FCA5A5' }]}>
            <Text style={[styles.statText, { color: '#B91C1C' }]}>
              ⚠ Hỏng hóc: {item.damaged_count}
            </Text>
          </View>
        )}
      </View>

      <View style={styles.cardFooter}>
        <View style={styles.footerItem}>
          <Text style={styles.footerText}>Tổng thiết bị đã nhận: {item.total_items_count}</Text>
        </View>
        {item.return_date ? (
          <View style={styles.footerItem}>
            <Calendar color="#64748B" size={14} />
            <Text style={styles.footerText}>Ngày trả: {item.return_date}</Text>
          </View>
        ) : null}
      </View>
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={onBack} style={styles.backBtn}>
          <ArrowLeft color="#0F172A" size={22} />
        </TouchableOpacity>
        <View style={styles.headerTitleWrap}>
          <Text style={styles.headerTitle}>Thu Hồi & Trả Kho</Text>
          <Text style={styles.headerSubtitle}>Danh sách đợt kiểm đếm sau sự kiện</Text>
        </View>
      </View>

      {/* Search Bar */}
      <View style={styles.searchWrap}>
        <Search color="#94A3B8" size={18} style={{ marginRight: 8 }} />
        <TextInput
          style={styles.searchInput}
          placeholder="Tìm theo mã RET, số đơn hoặc khách hàng..."
          placeholderTextColor="#94A3B8"
          value={searchTerm}
          onChangeText={setSearchTerm}
        />
      </View>

      {isLoading ? (
        <View style={styles.centerBox}>
          <ActivityIndicator color="#059669" size="large" />
          <Text style={styles.loadingText}>Đang tải danh sách đợt thu hồi...</Text>
        </View>
      ) : (
        <FlatList
          data={batches}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={styles.listContent}
          refreshControl={<RefreshControl refreshing={isRefreshing} onRefresh={onRefresh} tintColor="#059669" />}
          ListEmptyComponent={
            <View style={styles.emptyBox}>
              <Text style={styles.emptyTitle}>Không có đợt thu hồi nào</Text>
              <Text style={styles.emptyText}>Hiện chưa có phiếu trả kho nào phù hợp.</Text>
            </View>
          }
        />
      )}
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
    paddingHorizontal: 16,
    paddingVertical: 14,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  backBtn: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#F1F5F9',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  headerTitleWrap: {
    flex: 1,
  },
  headerTitle: {
    color: '#0F172A',
    fontSize: 18,
    fontWeight: '800',
  },
  headerSubtitle: {
    color: '#64748B',
    fontSize: 12,
    marginTop: 2,
  },
  searchWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    margin: 16,
    paddingHorizontal: 14,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#CBD5E1',
    height: 46,
  },
  searchInput: {
    flex: 1,
    color: '#0F172A',
    fontSize: 14,
  },
  listContent: {
    paddingHorizontal: 16,
    paddingBottom: 24,
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#64748B',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 6,
    elevation: 2,
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  codeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  batchCode: {
    color: '#059669',
    fontSize: 15,
    fontWeight: '800',
  },
  eventName: {
    color: '#0F172A',
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 4,
  },
  customerName: {
    color: '#64748B',
    fontSize: 13,
    marginBottom: 12,
  },
  gradingStatsRow: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 12,
  },
  statBadge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
    borderWidth: 1,
  },
  statText: {
    fontSize: 12,
    fontWeight: '700',
  },
  cardFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
  },
  footerItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  footerText: {
    color: '#64748B',
    fontSize: 12,
  },
  centerBox: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  loadingText: {
    color: '#64748B',
    fontSize: 14,
    marginTop: 12,
  },
  emptyBox: {
    padding: 32,
    alignItems: 'center',
  },
  emptyTitle: {
    color: '#334155',
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 6,
  },
  emptyText: {
    color: '#94A3B8',
    fontSize: 13,
    textAlign: 'center',
  },
});

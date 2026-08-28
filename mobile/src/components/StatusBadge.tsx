import React from 'react';
import { StyleSheet, Text, View } from 'react-native';

interface StatusBadgeProps {
  label: string;
  color?: string;
  size?: 'sm' | 'md';
}

export const StatusBadge: React.FC<StatusBadgeProps> = ({ label, color = 'gray', size = 'md' }) => {
  const getBadgeStyle = () => {
    switch (color) {
      case 'success':
      case 'emerald':
        return { bg: '#DCFCE7', text: '#15803D', border: '#86EFAC' };
      case 'warning':
      case 'amber':
        return { bg: '#FEF3C7', text: '#B45309', border: '#FDE68A' };
      case 'danger':
      case 'rose':
        return { bg: '#FEE2E2', text: '#B91C1C', border: '#FCA5A5' };
      case 'info':
      case 'sky':
      case 'primary':
        return { bg: '#E0F2FE', text: '#0369A1', border: '#BAE6FD' };
      default:
        return { bg: '#F3F4F6', text: '#4B5563', border: '#E5E7EB' };
    }
  };

  const styleConfig = getBadgeStyle();
  const isSmall = size === 'sm';

  return (
    <View
      style={[
        styles.badge,
        {
          backgroundColor: styleConfig.bg,
          borderColor: styleConfig.border,
          paddingVertical: isSmall ? 2 : 4,
          paddingHorizontal: isSmall ? 6 : 10,
        },
      ]}
    >
      <Text
        style={[
          styles.text,
          {
            color: styleConfig.text,
            fontSize: isSmall ? 11 : 13,
            fontWeight: isSmall ? '600' : '700',
          },
        ]}
      >
        {label}
      </Text>
    </View>
  );
};

const styles = StyleSheet.create({
  badge: {
    borderRadius: 9999,
    borderWidth: 1,
    alignSelf: 'flex-start',
  },
  text: {
    letterSpacing: 0.2,
  },
});

import { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TextInput,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { apiService, Recipe, Category } from '@/services/api';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { BottomNavigation } from '@/components/BottomNavigation';

export default function EditRecipeScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const colorScheme = useColorScheme();
  const colors = Colors[colorScheme ?? 'light'];

  const [recipe, setRecipe] = useState<Recipe | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    category_id: null as number | null,
    prep_time_minutes: '',
    servings: '',
    instructions: '',
    ingredients: '',
  });

  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(false);
  const [loadingRecipe, setLoadingRecipe] = useState(true);
  const [loadingCategories, setLoadingCategories] = useState(true);

  useEffect(() => {
    loadCategories();
    loadRecipe();
  }, [id]);

  const loadRecipe = async () => {
    try {
      setLoadingRecipe(true);
      const response = await apiService.getRecipe(Number(id));
      const recipeData = response.data;
      setRecipe(recipeData);
      setFormData({
        name: recipeData.name || '',
        category_id: recipeData.category_id || null,
        prep_time_minutes: recipeData.prep_time_minutes?.toString() || '',
        servings: recipeData.servings?.toString() || '',
        instructions: recipeData.instructions || '',
        ingredients: recipeData.ingredients || '',
      });
    } catch (error) {
      console.error('Erro ao carregar receita:', error);
      Alert.alert('Erro', 'Não foi possível carregar a receita');
      router.back();
    } finally {
      setLoadingRecipe(false);
    }
  };

  const loadCategories = async () => {
    try {
      const response = await apiService.getCategories();
      setCategories(response.data);
    } catch (error) {
      console.error('Erro ao carregar categorias:', error);
    } finally {
      setLoadingCategories(false);
    }
  };

  const handleSubmit = async () => {
    if (!formData.instructions.trim()) {
      Alert.alert('Erro', 'O modo de preparo é obrigatório');
      return;
    }

    try {
      setLoading(true);
      const data = {
        name: formData.name || undefined,
        category_id: formData.category_id || null,
        prep_time_minutes: formData.prep_time_minutes
          ? Number(formData.prep_time_minutes)
          : null,
        servings: formData.servings ? Number(formData.servings) : null,
        instructions: formData.instructions,
        ingredients: formData.ingredients || null,
      };

      await apiService.updateRecipe(Number(id), data);
      Alert.alert('Sucesso', 'Receita atualizada com sucesso');
      router.back();
    } catch (error: any) {
      console.error('Erro ao atualizar receita:', error);
      Alert.alert('Erro', error.message || 'Não foi possível atualizar a receita');
    } finally {
      setLoading(false);
    }
  };

  if (loadingRecipe) {
    return (
      <View style={[styles.container, styles.centerContent, { backgroundColor: colors.background }]}>
        <ActivityIndicator size="large" color={colors.tint} />
      </View>
    );
  }

  return (
    <ScrollView style={[styles.container, { backgroundColor: colors.background }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backButton}>
          <IconSymbol name="chevron.left" size={24} color={colors.text} />
        </TouchableOpacity>
        <Text style={[styles.title, { color: colors.text }]}>Editar Receita</Text>
        <View style={styles.placeholder} />
      </View>

      <View style={styles.form}>
        <View style={styles.inputGroup}>
          <Text style={[styles.label, { color: colors.text }]}>Nome da Receita</Text>
          <TextInput
            style={[styles.input, { color: colors.text, borderColor: colors.icon }]}
            placeholder="Ex: Bolo de Chocolate"
            placeholderTextColor={colors.icon}
            value={formData.name}
            onChangeText={(text) => setFormData({ ...formData, name: text })}
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={[styles.label, { color: colors.text }]}>Categoria</Text>
          {loadingCategories ? (
            <ActivityIndicator size="small" color={colors.tint} />
          ) : (
            <ScrollView horizontal showsHorizontalScrollIndicator={false}>
              <View style={styles.categoryContainer}>
                <TouchableOpacity
                  style={[
                    styles.categoryChip,
                    formData.category_id === null && styles.categoryChipActive,
                    { borderColor: colors.icon },
                    formData.category_id === null && { backgroundColor: colors.tint },
                  ]}
                  onPress={() => setFormData({ ...formData, category_id: null })}>
                  <Text
                    style={[
                      styles.categoryChipText,
                      { color: formData.category_id === null ? '#fff' : colors.text },
                    ]}>
                    Nenhuma
                  </Text>
                </TouchableOpacity>
                {categories.map((category) => (
                  <TouchableOpacity
                    key={category.id}
                    style={[
                      styles.categoryChip,
                      formData.category_id === category.id && styles.categoryChipActive,
                      { borderColor: colors.icon },
                      formData.category_id === category.id && { backgroundColor: colors.tint },
                    ]}
                    onPress={() => setFormData({ ...formData, category_id: category.id })}>
                    <Text
                      style={[
                        styles.categoryChipText,
                        {
                          color: formData.category_id === category.id ? '#fff' : colors.text,
                        },
                      ]}>
                      {category.name}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>
            </ScrollView>
          )}
        </View>

        <View style={styles.row}>
          <View style={[styles.inputGroup, styles.halfWidth]}>
            <Text style={[styles.label, { color: colors.text }]}>Tempo de Preparo (min)</Text>
            <TextInput
              style={[styles.input, { color: colors.text, borderColor: colors.icon }]}
              placeholder="30"
              placeholderTextColor={colors.icon}
              keyboardType="numeric"
              value={formData.prep_time_minutes}
              onChangeText={(text) => setFormData({ ...formData, prep_time_minutes: text })}
            />
          </View>

          <View style={[styles.inputGroup, styles.halfWidth]}>
            <Text style={[styles.label, { color: colors.text }]}>Porções</Text>
            <TextInput
              style={[styles.input, { color: colors.text, borderColor: colors.icon }]}
              placeholder="4"
              placeholderTextColor={colors.icon}
              keyboardType="numeric"
              value={formData.servings}
              onChangeText={(text) => setFormData({ ...formData, servings: text })}
            />
          </View>
        </View>

        <View style={styles.inputGroup}>
          <Text style={[styles.label, { color: colors.text }]}>Ingredientes</Text>
          <TextInput
            style={[
              styles.textArea,
              { color: colors.text, borderColor: colors.icon },
            ]}
            placeholder="Liste os ingredientes..."
            placeholderTextColor={colors.icon}
            multiline
            numberOfLines={6}
            value={formData.ingredients}
            onChangeText={(text) => setFormData({ ...formData, ingredients: text })}
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={[styles.label, { color: colors.text }]}>
            Modo de Preparo <Text style={{ color: '#ff4444' }}>*</Text>
          </Text>
          <TextInput
            style={[
              styles.textArea,
              { color: colors.text, borderColor: colors.icon },
            ]}
            placeholder="Descreva o modo de preparo..."
            placeholderTextColor={colors.icon}
            multiline
            numberOfLines={10}
            value={formData.instructions}
            onChangeText={(text) => setFormData({ ...formData, instructions: text })}
          />
        </View>

        <TouchableOpacity
          style={[styles.submitButton, { backgroundColor: colors.tint }]}
          onPress={handleSubmit}
          disabled={loading}>
          {loading ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.submitButtonText}>Salvar Alterações</Text>
          )}
        </TouchableOpacity>
      </View>
      <BottomNavigation />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  centerContent: {
    justifyContent: 'center',
    alignItems: 'center',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    paddingTop: 60,
  },
  backButton: {
    padding: 4,
  },
  title: {
    fontSize: 20,
    fontWeight: '600',
  },
  placeholder: {
    width: 32,
  },
  form: {
    padding: 16,
    paddingBottom: 100, // Espaço para o BottomNavigation
  },
  inputGroup: {
    marginBottom: 20,
  },
  label: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 8,
  },
  input: {
    borderWidth: 1,
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
  },
  textArea: {
    borderWidth: 1,
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
    minHeight: 120,
    textAlignVertical: 'top',
  },
  row: {
    flexDirection: 'row',
    gap: 12,
  },
  halfWidth: {
    flex: 1,
  },
  categoryContainer: {
    flexDirection: 'row',
    gap: 8,
  },
  categoryChip: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
    borderWidth: 1,
  },
  categoryChipActive: {
    borderWidth: 0,
  },
  categoryChipText: {
    fontSize: 14,
    fontWeight: '500',
  },
  submitButton: {
    padding: 16,
    borderRadius: 8,
    alignItems: 'center',
    marginTop: 8,
    marginBottom: 32,
  },
  submitButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
});


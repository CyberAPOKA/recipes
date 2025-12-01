import axios from 'axios'

const OPENAI_API_KEY = import.meta.env.VITE_OPENAI_API_KEY
const OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions'

/**
 * Gera uma receita usando ChatGPT
 */
export async function generateRecipeWithAI(categoryName, recipeName) {
    if (!OPENAI_API_KEY) {
        throw new Error('OpenAI API key not configured')
    }

    // Validar que a categoria foi fornecida
    if (!categoryName || categoryName.trim() === '') {
        throw new Error('Categoria é obrigatória para gerar a receita')
    }

    const recipePrompt = recipeName
        ? `Crie uma receita completa chamada "${recipeName}" na categoria "${categoryName}". A receita DEVE ser adequada e relacionada à categoria "${categoryName}".`
        : `Crie uma receita completa na categoria "${categoryName}". A receita DEVE ser adequada e relacionada à categoria "${categoryName}".`

    const prompt = `${recipePrompt}

IMPORTANTE: A receita deve ser adequada para a categoria "${categoryName}". Por exemplo:
- Se a categoria for "Alimentação Saudável", a receita deve ser saudável, nutritiva e balanceada
- Se a categoria for "Bolos e tortas doces", a receita deve ser um bolo ou torta doce
- Se a categoria for "Sobremesas", a receita deve ser uma sobremesa
- E assim por diante para outras categorias

Por favor, forneça a receita no seguinte formato JSON válido (sem markdown, sem código, apenas JSON puro):
{
  "name": "Nome da receita",
  "prep_time_minutes": número em minutos (apenas número, sem texto),
  "servings": número de porções (apenas número, sem texto),
  "ingredients": "Lista de ingredientes com quantidades em formato HTML. Use tags <ul> e <li> para listar cada ingrediente. Exemplo: <ul><li>2 xícaras de farinha</li><li>3 ovos</li></ul>",
  "instructions": "Instruções passo a passo de como preparar a receita em formato HTML. Use tags <ol> e <li> para os passos numerados, e <p> para parágrafos quando necessário. Exemplo: <ol><li><p>Primeiro passo detalhado</p></li><li><p>Segundo passo detalhado</p></li></ol>"
}

REGRAS IMPORTANTES:
- Retorne APENAS o JSON válido, sem markdown, sem texto antes ou depois
- Os ingredientes devem estar em formato HTML com <ul> e <li>
- As instruções devem estar em formato HTML com <ol> e <li> para os passos
- Seja específico com as quantidades dos ingredientes (ex: "2 xícaras", "500g", "3 colheres de sopa")
- As instruções devem ser claras, detalhadas e passo a passo
- prep_time_minutes e servings devem ser números inteiros
- Se o nome da receita não foi fornecido, crie um nome apropriado baseado na categoria`

    try {
        const response = await axios.post(
            OPENAI_API_URL,
            {
                model: 'gpt-4o-mini',
                messages: [
                    {
                        role: 'system',
                        content: `Você é um chef experiente que cria receitas detalhadas e precisas. IMPORTANTE: Você DEVE criar receitas que sejam adequadas à categoria especificada. Se a categoria for "Alimentação Saudável", crie receitas saudáveis e nutritivas. Se for "Bolos e tortas doces", crie bolos ou tortas doces. Sempre retorne apenas JSON válido sem markdown ou texto adicional.`
                    },
                    {
                        role: 'user',
                        content: prompt
                    }
                ],
                temperature: 0.7,
                max_tokens: 2000,
            },
            {
                headers: {
                    'Authorization': `Bearer ${OPENAI_API_KEY}`,
                    'Content-Type': 'application/json',
                },
            }
        )

        const content = response.data.choices[0]?.message?.content

        if (!content) {
            throw new Error('No response from OpenAI')
        }

        // Extrair JSON da resposta (pode ter markdown code blocks)
        let jsonContent = content.trim()

        // Remover markdown code blocks se existirem
        jsonContent = jsonContent.replace(/^```json\n?/i, '').replace(/^```\n?/i, '').replace(/\n?```$/i, '')
        jsonContent = jsonContent.trim()

        // Tentar encontrar o JSON dentro do texto se não começar com {
        if (!jsonContent.startsWith('{')) {
            const jsonMatch = jsonContent.match(/\{[\s\S]*\}/)
            if (jsonMatch) {
                jsonContent = jsonMatch[0]
            }
        }

        let recipeData
        try {
            recipeData = JSON.parse(jsonContent)
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError)
            console.error('Content received:', jsonContent)
            throw new Error('Erro ao processar resposta da IA. A resposta não está em formato JSON válido.')
        }

        return {
            success: true,
            data: {
                name: recipeData.name || recipeName || '',
                prep_time_minutes: recipeData.prep_time_minutes || null,
                servings: recipeData.servings || null,
                ingredients: recipeData.ingredients || '',
                instructions: recipeData.instructions || '',
            }
        }
    } catch (error) {
        console.error('OpenAI API Error:', error)

        if (error.response?.data?.error?.message) {
            throw new Error(error.response.data.error.message)
        }

        if (error.message.includes('JSON')) {
            throw new Error('Erro ao processar resposta da IA. Tente novamente.')
        }

        throw new Error(error.message || 'Erro ao gerar receita com IA')
    }
}


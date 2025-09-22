# FotoFix Instruction Modification Workflow

## Where the Instructions Are Located

### 1. Main Instructions File
**File**: `api/enhancement_instructions.php`
**Location**: Lines 44-48 (furniture modernization)
**Purpose**: Contains all enhancement instructions used by the AI

### 2. Easy-to-Edit Markdown File
**File**: `ENHANCEMENT_INSTRUCTIONS.md`
**Purpose**: Human-readable format for easy modification

### 3. Update Script
**File**: `update_instructions.php`
**Purpose**: Syncs changes from markdown to PHP file

## Current Furniture Replacement Instruction

**Location**: `api/enhancement_instructions.php` line 47
```php
'instructions' => 'Replace any old, worn, or outdated furniture with modern, stylish pieces that appeal to contemporary buyers. Use neutral, elegant furniture that complements the space. Remove any furniture that makes the space look cluttered or dated.'
```

## How to Modify Instructions

### Method 1: Direct PHP Edit (Quick)
1. Open `api/enhancement_instructions.php`
2. Find the instruction you want to modify (line 47 for furniture)
3. Edit the text between the single quotes
4. Save the file

### Method 2: Markdown Edit (Recommended)
1. Open `ENHANCEMENT_INSTRUCTIONS.md`
2. Find the instruction you want to modify
3. Edit the text in the code block (between ``` and ```)
4. Run the update script: `php update_instructions.php`
5. The PHP file will be automatically updated

## Example: Improving Furniture Instructions

### Current Instruction:
```
Replace any old, worn, or outdated furniture with modern, stylish pieces that appeal to contemporary buyers. Use neutral, elegant furniture that complements the space. Remove any furniture that makes the space look cluttered or dated.
```

### Enhanced Instruction:
```
Replace any old, worn, or outdated furniture with modern, stylish pieces that appeal to contemporary buyers. For living rooms, use clean-lined sofas and coffee tables. For bedrooms, add platform beds with modern headboards. For dining areas, use contemporary dining sets with sleek lines. Use neutral colors like grays, whites, and light woods. Remove any furniture that makes the space look cluttered, dated, or too personal. Ensure all furniture is properly scaled to the room size and creates a sense of spaciousness.
```

## Testing Your Changes

1. **Run the test script**: `php test_setup.php`
2. **Upload test images** through the web interface
3. **Check the logs** at `/mnt/docker/apps/logs/fotofix/debug.log`
4. **Verify results** in the enhanced image previews

## Key Instruction Categories

### Exterior Instructions:
- `landscaping` - Grass and plant improvements
- `sky_weather` - Sky and weather enhancement
- `exterior_cleaning` - Surface cleaning
- `outdoor_furniture` - Outdoor furniture
- `lighting` - Exterior lighting

### Interior Instructions:
- `furniture_modernization` - **Furniture replacement** (line 47)
- `cleaning_decluttering` - Clean and declutter
- `lighting_enhancement` - Interior lighting
- `color_scheme` - Color updates
- `decorative_touches` - Decorative elements

## Tips for Better Instructions

1. **Be Specific**: Instead of "modern furniture", specify "clean-lined sofas and contemporary dining sets"
2. **Use Action Words**: "Replace", "Add", "Remove", "Enhance"
3. **Include Context**: Mention specific rooms or areas
4. **Set Expectations**: "Appeal to contemporary buyers", "Create spaciousness"
5. **Maintain Safety**: Always end with structural preservation reminders

## File Structure
```
fotofix/
├── api/
│   └── enhancement_instructions.php  ← Main instructions (edit here or via markdown)
├── ENHANCEMENT_INSTRUCTIONS.md      ← Easy-to-edit markdown file
├── update_instructions.php          ← Sync script
└── INSTRUCTION_WORKFLOW.md          ← This file
```

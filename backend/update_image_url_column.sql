-- 更新 image_url 字段为 LONGTEXT 以支持 base64 图片数据
USE ai_chat_system;

ALTER TABLE image_generations 
MODIFY COLUMN image_url LONGTEXT;

-- 验证修改
DESCRIBE image_generations;
